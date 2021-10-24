<?php
namespace OCA\DuplicateFinder\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISubAdminSettings;
use OCA\DuplicateFinder\Service\ConfigService;

class Admin implements ISubAdminSettings
{

    /** @var ConfigService */
    private $config;

    public function __construct(ConfigService $config)
    {
        $this->config = $config;
    }

    /**
     * @return TemplateResponse
     */
    public function getForm(): TemplateResponse
    {
        return new TemplateResponse('duplicatefinder', 'settings-admin', array(), '');
    }

    public function getSection(): string
    {
        return 'duplicatefinder';
    }

    public function getPriority(): int
    {
        return 0;
    }
}
