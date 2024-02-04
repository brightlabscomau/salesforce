<?php

namespace brightlabs\craftsalesforce\elements;

use Craft;
use brightlabs\craftsalesforce\elements\conditions\AssignmentCondition;
use brightlabs\craftsalesforce\elements\db\AssignmentQuery;
use brightlabs\craftsalesforce\Salesforce;
use craft\base\Element;
use craft\elements\User;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use craft\web\CpScreenResponseBehavior;
use yii\web\Response;
use craft\helpers\Db;

/**
 * Assignment element type
 */
class Assignment extends Element
{
    public string $handle = 'assignment';
    public ?string $salesforceId = null;
    public ?string $hybridVolunteeringNature = null;
    public ?string $workplace = null;
    public ?string $duration = null;
    public ?string $startDate = null;
    public ?string $positionDescriptionUrl = null;
    public ?string $applicationCloseDate = null;
    public ?string $positionSummary = null;
    public ?string $baseAllowance = null;
    public ?string $livingAllowance = null;
    public ?string $specialConditions = null;
    public ?string $sector = null;
    public ?string $country = null;
    public ?string $publish = null;
    public ?string $recruitmentStartDate = null;
    public ?string $recruitmentEndDate = null;
    public ?string $jsonContent = null;

    public static function displayName(): string
    {
        return Craft::t('salesforce', 'Assignment');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('salesforce', 'assignment');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('salesforce', 'Assignments');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('salesforce', 'assignments');
    }

    public static function refHandle(): ?string
    {
        return 'assignment';
    }

    public static function trackChanges(): bool
    {
        return true;
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return false;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return Craft::createObject(AssignmentQuery::class, [static::class]);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(AssignmentCondition::class, [static::class]);
    }

    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('salesforce', 'All assignments'),
            ],
        ];
    }

    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        return [];
    }

    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
            // ...
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'salesforceId' => ['label' => Craft::t('salesforce', 'Salesforce Id')],
            'country' => ['label' => Craft::t('salesforce', 'Country')],
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            // ...
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            // 'link',
            'country',
            'dateCreated',
            // ...
        ];
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    public function getUriFormat(): ?string
    {
        // If assignments should have URLs, define their URI format here
        return "assignments/{$this->slug}";
    }

    protected function previewTargets(): array
    {
        $previewTargets = [];
        $url = $this->getUrl();
        if ($url) {
            $previewTargets[] = [
                'label' => Craft::t('app', 'Primary {type} page', [
                    'type' => self::lowerDisplayName(),
                ]),
                'url' => $url,
            ];
        }
        return $previewTargets;
    }

    protected function route(): array|string|null
    {
        // Define how assignments should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'assignments/_assignment',
                'variables' => ['assignment' => $this],
            ]
        ];
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('viewAssignments');
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('saveAssignments');
    }

    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('saveAssignments');
    }

    public function canDelete(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('deleteAssignments');
    }

    public function canCreateDrafts(User $user): bool
    {
        return false;
    }

    protected function cpEditUrl(): ?string
    {
        return sprintf('salesforce/assignments/%s', $this->getCanonicalId());
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('salesforce/assignments');
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('salesforce/assignments'),
            ],
        ]);
    }

    protected static function defineSearchableAttributes(): array
    {
        return [
            'title',
            'country',
            'hybridVolunteeringNature',
        ];
    }


    public function afterSave(bool $isNew): void
    {
        Db::upsert('{{%salesforce_assignments}}', [
            'id' => $this->id,
        ], [
            'salesforceId' => $this->salesforceId,
            'hybridVolunteeringNature' => $this->hybridVolunteeringNature,
            'workplace' => $this->workplace,
            'duration' => $this->duration,
            'startDate' => $this->startDate,
            'positionDescriptionUrl' => $this->positionDescriptionUrl,
            'applicationCloseDate' => $this->applicationCloseDate,
            'positionSummary' => $this->positionSummary,
            'baseAllowance' => $this->baseAllowance,
            'livingAllowance' => $this->livingAllowance,
            'specialConditions' => $this->specialConditions,
            'sector' => $this->sector,
            'country' => $this->country,
            'publish' => $this->publish,
            'recruitmentStartDate' => $this->recruitmentStartDate,
            'recruitmentEndDate' => $this->recruitmentEndDate,
            'jsonContent' => $this->jsonContent,
        ]);

        if (!$this->propagating) {
            Db::upsert('{{%salesforce_assignments}}', [
                'id' => $this->id,
            ], [
                'salesforceId' => $this->salesforceId,
                'hybridVolunteeringNature' => $this->hybridVolunteeringNature,
                'workplace' => $this->workplace,
                'duration' => $this->duration,
                'startDate' => $this->startDate,
                'positionDescriptionUrl' => $this->positionDescriptionUrl,
                'applicationCloseDate' => $this->applicationCloseDate,
                'positionSummary' => $this->positionSummary,
                'baseAllowance' => $this->baseAllowance,
                'livingAllowance' => $this->livingAllowance,
                'specialConditions' => $this->specialConditions,
                'sector' => $this->sector,
                'country' => $this->country,
                'publish' => $this->publish,
                'recruitmentStartDate' => $this->recruitmentStartDate,
                'recruitmentEndDate' => $this->recruitmentEndDate,
                'jsonContent' => $this->jsonContent,
            ]);
        }

        parent::afterSave($isNew);
    }
}
