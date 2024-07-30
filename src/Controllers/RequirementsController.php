<?php

namespace RachidLaasri\LaravelInstaller\Controllers;

use Illuminate\Routing\Controller;
use RachidLaasri\LaravelInstaller\Helpers\RequirementsChecker;
use Illuminate\Support\Facades\Process;

class RequirementsController extends Controller
{
    /**
     * @var RequirementsChecker
     */
    protected $requirements;

    /**
     * @param RequirementsChecker $checker
     */
    public function __construct(RequirementsChecker $checker)
    {
        $this->requirements = $checker;
    }

    /**
     * Display the requirements page.
     *
     * @return \Illuminate\View\View
     */
    public function requirements()
    {
        $phpSupportInfo = $this->requirements->checkPHPversion(
            config('installer.core.minPhpVersion')
        );
        $requirements = $this->requirements->check(
            config('installer.requirements')
        );
        
        $javaInfo = $this->requirements->isJavaVersionBetween(config('installer.requirements.java'));
            
        return view('vendor.installer.requirements', compact('requirements', 'phpSupportInfo', 'javaInfo'));
    }
}
