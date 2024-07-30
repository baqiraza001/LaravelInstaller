<?php

namespace RachidLaasri\LaravelInstaller\Helpers;

use Exception;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class DatabaseManager
{
    /**
     * Migrate and seed the database.
     *
     * @return array
     */
    public function migrateAndSeed()
    {
        $outputLog = new BufferedOutput;

        $this->sqlite($outputLog);

        return $this->migrate($outputLog);
    }

    /**
     * Run the migration and call the seeder.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return array
     */
    private function migrate(BufferedOutput $outputLog)
    {
        try {
            Artisan::call('migrate', ['--force'=> true], $outputLog);
        } catch (Exception $e) {
            return $this->response($e->getMessage(), 'error', $outputLog);
        }

        return $this->seed($outputLog);
    }

    /**
     * Seed the database.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return array
     */
    private function seed(BufferedOutput $outputLog)
    {
        try {
            
            Artisan::call('db:seed', ['--force' => true], $outputLog);
            
            $accountData = file_exists(storage_path('accountData.txt')) ? json_decode(file_get_contents(storage_path('accountData.txt'))) : '';
            $email = $accountData->email ?? 'admin@example.com';
            $password = $accountData->password ?? 'admin@123';
            
            $existingUser = DB::table('users')->where('email', $email)->first();
          	if (!$existingUser) 
          	{
          		$user_data = [
          			'first_name' => 'admin',
          			'last_name' => 'admin',
          			'profile_pic' => 'admin',
          			'phone_number' => '00000000000',
          			'email' => $email,
                    'password' => Hash::make($password), // password is admin@123
                    'password_reset_code' => '',
                    'email_verification_code' => NULL,
                    'country' => '',
                    'active' => 1,
                    'created_at' => now()->toDateString(),
                    'updated_at' => now(),
                    'user_type' => 1,
                    'is_deleted' => 0,
              ];
        
              $user_id = DB::table('users')->insertGetId($user_data);
        
              DB::table('api_keys')->insert([
              	'user_id' => $user_id,
              	'api_key' => bin2hex(random_bytes(32)),
              	'level' => 1,
              	'ignore_limits' => 0,
              	'is_private_key' => 0,
              	'ip_addresses' => NULL,
              	'api_key_label' => 'Default',
              	'active' => 1,
              ]);
            }
            
            unlink(storage_path('accountData.txt'));
            
        } catch (Exception $e) {
            return $this->response($e->getMessage(), 'error', $outputLog);
        }

        return $this->response(trans('installer_messages.final.finished'), 'success', $outputLog);
    }

    /**
     * Return a formatted error messages.
     *
     * @param string $message
     * @param string $status
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return array
     */
    private function response($message, $status, BufferedOutput $outputLog)
    {
        return [
            'status' => $status,
            'message' => $message,
            'dbOutputLog' => $outputLog->fetch(),
        ];
    }

    /**
     * Check database type. If SQLite, then create the database file.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     */
    private function sqlite(BufferedOutput $outputLog)
    {
        if (DB::connection() instanceof SQLiteConnection) {
            $database = DB::connection()->getDatabaseName();
            if (! file_exists($database)) {
                touch($database);
                DB::reconnect(Config::get('database.default'));
            }
            $outputLog->write('Using SqlLite database: '.$database, 1);
        }
    }
}
