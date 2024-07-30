<?php

namespace RachidLaasri\LaravelInstaller\Helpers;
use Illuminate\Support\Facades\Process;

class RequirementsChecker
{
    /**
     * Minimum PHP Version Supported (Override is in installer.php config file).
     *
     * @var _minPhpVersion
     */
    private $_minPhpVersion = '7.0.0';

    /**
     * Check for the server requirements.
     *
     * @param array $requirements
     * @return array
     */
    public function check(array $requirements)
    {
        $results = [];

        foreach ($requirements as $type => $requirement) {
            switch ($type) {
                // check php requirements
                case 'php':
                    foreach ($requirements[$type] as $requirement) {
                        $results['requirements'][$type][$requirement] = true;

                        if (! extension_loaded($requirement)) {
                            $results['requirements'][$type][$requirement] = false;
                            $results['errors'] = true;
                        }
                    }
                    break;
                // check functions requirements
                case 'functions':
                    foreach ($requirements[$type] as $requirement) {
                        $results['requirements'][$type][$requirement] = true;

                        if (! function_exists($requirement)) {
                            $results['requirements'][$type][$requirement] = false;
                            $results['errors'] = true;
                        }
                    }
                    break;
                // check java requirements
                case 'java':
                    $results['requirements'][$type]['Compatible'] = true;
                    $supported = $this->isJavaVersionBetween($requirements[$type])['supported'];
                    if (! $supported) {
                        $results['requirements'][$type]['Compatible'] = false;
                        $results['errors'] = true;
                    }
                    break;
                // check jq requirements
                case 'jq':
                    $jqInstalled = $this->isJqInstalled();
                    if(!$jqInstalled)
                        $results['errors'] = !$jqInstalled;
                    $results['requirements'][$type]['Installed'] = $jqInstalled;
                    break;
                // check fatoora requirements
                case 'fatoora':
                    $fatooraInstalled = $this->isFatooraInstalled();
                    if(!$fatooraInstalled)
                        $results['errors'] = !$fatooraInstalled;
                    $results['requirements'][$type]['Installed'] = $fatooraInstalled;
                    break;
                // check apache requirements
                case 'apache':
                    foreach ($requirements[$type] as $requirement) {
                        // if function doesn't exist we can't check apache modules
                        if (function_exists('apache_get_modules')) {
                            $results['requirements'][$type][$requirement] = true;

                            if (! in_array($requirement, apache_get_modules())) {
                                $results['requirements'][$type][$requirement] = false;

                                $results['errors'] = true;
                            }
                        }
                    }
                    break;
            }
        }

        return $results;
    }

    /**
     * Check PHP version requirement.
     *
     * @return array
     */
    public function checkPHPversion(string $minPhpVersion = null)
    {
        $minVersionPhp = $minPhpVersion;
        $currentPhpVersion = $this->getPhpVersionInfo();
        $supported = false;

        if ($minPhpVersion == null) {
            $minVersionPhp = $this->getMinPhpVersion();
        }

        if (version_compare($currentPhpVersion['version'], $minVersionPhp) >= 0) {
            $supported = true;
        }

        $phpStatus = [
            'full' => $currentPhpVersion['full'],
            'current' => $currentPhpVersion['version'],
            'minimum' => $minVersionPhp,
            'supported' => $supported,
        ];

        return $phpStatus;
    }

    /**
     * Get current Php version information.
     *
     * @return array
     */
    private static function getPhpVersionInfo()
    {
        $currentVersionFull = PHP_VERSION;
        preg_match("#^\d+(\.\d+)*#", $currentVersionFull, $filtered);
        $currentVersion = $filtered[0];

        return [
            'full' => $currentVersionFull,
            'version' => $currentVersion,
        ];
    }

    /**
     * Get minimum PHP version ID.
     *
     * @return string _minPhpVersion
     */
    protected function getMinPhpVersion()
    {
        return $this->_minPhpVersion;
    }
    
    public function isJavaVersionBetween($data=[]) 
    {
        $minVersion= $data['minVersion']; 
        $maxVersion= $data['maxVersion']; 

        $process = Process::forever()->run('java -version 2>&1');
    
        if (!$process->successful())
            $supported = false;
    
        $output = $process->output();
    
        // Extract the version number
        preg_match('/version "([\d.]+)_?\d*"/', $output, $matches);
    
        if (isset($matches[1])) {
            $version = $matches[1];
            $supported = version_compare($version, $minVersion, '>=') && version_compare($version, $maxVersion, '<=');
        }
        else
            $supported = false;
        
         return [
            'supported' => $supported,
            'current' => $version ?? null,
            'minVersion' => $minVersion,
            'maxVersion' => $maxVersion,
        ];
    }
    
    public function isJqInstalled() 
    {
        $process = Process::forever()->run('jq --version 2>&1');
        return $process->successful();
    }
    
    public function isFatooraInstalled() 
    {
        $process = Process::forever()->run('fatoora 2>&1');
        return $process->successful();
    }
    
    
}
