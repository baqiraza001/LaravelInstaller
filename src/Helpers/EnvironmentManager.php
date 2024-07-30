<?php

namespace RachidLaasri\LaravelInstaller\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Artisan;

class EnvironmentManager
{
    /**
     * @var string
     */
    private $envPath;

    /**
     * @var string
     */
    private $envExamplePath;

    /**
     * Set the .env and .env.example paths.
     */
    public function __construct()
    {
        $this->envPath = base_path('.env');
        $this->envExamplePath = base_path('.env.example');
    }

    /**
     * Get the content of the .env file.
     *
     * @return string
     */
    public function getEnvContent()
    {
        if (! file_exists($this->envPath)) {
            if (file_exists($this->envExamplePath)) {
                copy($this->envExamplePath, $this->envPath);
            } else {
                touch($this->envPath);
            }
        }

        return file_get_contents($this->envPath);
    }

    /**
     * Get the the .env file path.
     *
     * @return string
     */
    public function getEnvPath()
    {
        return $this->envPath;
    }

    /**
     * Get the the .env.example file path.
     *
     * @return string
     */
    public function getEnvExamplePath()
    {
        return $this->envExamplePath;
    }

    /**
     * Save the edited content to the .env file.
     *
     * @param Request $input
     * @return string
     */
    public function saveFileClassic(Request $input)
    {
        $message = trans('installer_messages.environment.success');

        try {
            unlink($this->envPath);
            file_put_contents($this->envPath, $input->get('envConfig'));
        } catch (Exception $e) {
            $message = trans('installer_messages.environment.errors');
        }

        return $message;
    }

    /**
     * Save the form content to the .env file.
     *
     * @param Request $request
     * @return string
     */
    public function saveFileWizard(Request $request)
    {
        $results = trans('installer_messages.environment.success');
        
        $envContent = file_get_contents($this->envPath);
        
        $data = $request->input();
        unset($data['_token']);
        foreach ($data as $key => $value) {
            $key = strtoupper($key);
            $envContent = preg_replace("/^$key=.*$/m", "$key=$value", $envContent);
        }
        
        $base_path = base_path()."/storage/app/public/zatca/sdk/";
        $env_path = $base_path."Apps";
        $fatooraPath = $base_path."Apps";
        $fatooraSdkPath = $base_path."Configuration/config.json";
    
        $process = Process::forever()->run('which java 2>&1');
        if (!$process->successful())
            $javaPath = '';
        else
            $javaPath = $process->output();
        
        $envContent = preg_replace("/^ENV_PATH=.*$/m", "ENV_PATH=$env_path", $envContent);
        $envContent = preg_replace("/^JAVA_PATH=.*$/m", "JAVA_PATH=$javaPath", $envContent);
        $envContent = preg_replace("/^FATOORA_PATH=.*$/m", "FATOORA_PATH=$fatooraPath", $envContent);
        $envContent = preg_replace("/^FATOORA_SDK_PATH=.*$/m", "FATOORA_SDK_PATH=$fatooraSdkPath", $envContent);
        
        // Check if the fatoora file is executable
        $fatooraFile = $fatooraPath."/fatoora";
        if (!is_executable($fatooraFile))
            Process::forever()->run("chmod +x $fatooraFile 2>&1");

        try {
            unlink($this->envPath);
            file_put_contents($this->envPath, $envContent);
            Artisan::call('key:generate', ['--force' => true]);
        } catch (Exception $e) {
            $results = trans('installer_messages.environment.errors');
        }

        return $results;
    }
}
