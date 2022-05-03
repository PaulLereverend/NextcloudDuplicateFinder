<?php
namespace OCA\DuplicateFinder\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISubAdminSettings;

use OCA\DuplicateFinder\AppInfo\Application;

class Admin implements ISubAdminSettings
{

    /**
     * @return TemplateResponse
     */
    public function getForm(): TemplateResponse
    {
        return new TemplateResponse(Application::ID, 'settings-admin', array(), '');
    }

    public function getSection(): string
    {
        return Application::ID;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
