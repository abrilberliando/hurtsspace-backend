<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Backup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminBackupController extends Controller
{
    // GET: List all backups
    public function index()
    {
        $backups = Backup::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $backups]);
    }

    // POST: Create a new backup
    public function store(Request $request)
    {
        try {
            $filename = 'backup_hspace_' . date('Y_m_d_His') . '.sql';
            $storagePath = storage_path('app/' . $filename);
            
            $dbHost = env('DB_HOST', '127.0.0.1');
            $dbPort = env('DB_PORT', '3306');
            $dbUsername = env('DB_USERNAME', 'root');
            $dbPassword = env('DB_PASSWORD', '');
            $dbDatabase = env('DB_DATABASE', 'hspace');

            // Dump database
            $command = "mysqldump --skip-ssl --no-tablespaces -h {$dbHost} -P {$dbPort} -u {$dbUsername} " .
                       ($dbPassword ? "-p{$dbPassword} " : "") .
                       "{$dbDatabase} > {$storagePath} 2>&1";
                       
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                Log::error("mysqldump failed: " . implode("\n", $output));
                return response()->json([
                    'message' => 'Failed to generate database backup',
                    'error' => implode("\n", $output)
                ], 500);
            }

            // Upload to Cloudinary as raw file
            $uploadResult = cloudinary()->uploadApi()->upload($storagePath, [
                'folder' => 'hspace/backups',
                'resource_type' => 'raw'
            ]);

            // Save to database
            $backup = Backup::create([
                'filename' => $filename,
                'file_url' => $uploadResult['secure_url']
            ]);

            // Delete local file
            if (file_exists($storagePath)) {
                unlink($storagePath);
            }

            return response()->json([
                'message' => 'Backup created successfully!',
                'data' => $backup
            ], 201);

        } catch (\Exception $e) {
            Log::error("Backup error: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred during backup: ' . $e->getMessage()], 500);
        }
    }

    // DELETE: Remove a backup
    public function destroy($id)
    {
        try {
            $backup = Backup::findOrFail($id);
            
            // Delete from Cloudinary
            // Cloudinary requires the public ID for raw files
            $parts = explode('/upload/', $backup->file_url);
            if (count($parts) == 2) {
                $publicIdWithExt = explode('/', $parts[1]);
                array_shift($publicIdWithExt); // Remove version
                $publicIdPath = implode('/', $publicIdWithExt);
                try {
                    cloudinary()->uploadApi()->destroy($publicIdPath, ['resource_type' => 'raw']);
                } catch (\Exception $e) {
                    Log::error("Failed to delete backup from Cloudinary: " . $e->getMessage());
                }
            }

            $backup->delete();

            return response()->json(['message' => 'Backup deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete backup: ' . $e->getMessage()], 500);
        }
    }
}
