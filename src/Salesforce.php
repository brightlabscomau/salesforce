<?php

namespace brightlabs\craftsalesforce;

use Craft;
use yii\base\Event;
use craft\base\Model;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\web\twig\variables\Cp;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterCpNavItemsEvent;
use brightlabs\craftsalesforce\models\Settings;

/**
 * Salesforce plugin
 *
 * @method static Salesforce getInstance()
 * @method Settings getSettings()
 * @author Bright Labs <devs@brightlabs.com.au>
 * @copyright Bright Labs
 * @license MIT
 */
class Salesforce extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            // ...
        });

        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_CP_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event) {
                // $event->navItems[] = [
                //     'url' => 'salesforce',
                //     'label' => 'Salesforce Sync',
                //     'id' => 'nav-salesforce'
                // ];
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['salesforce'] = 'salesforce/_settings.twig';
            }
        );
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('salesforce/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
    }
}
