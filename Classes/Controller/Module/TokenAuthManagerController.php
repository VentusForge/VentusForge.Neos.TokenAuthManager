<?php

declare(strict_types=1);

namespace VentusForge\Neos\TokenAuthManager\Controller\Module;

use Flownative\TokenAuthentication\Security\Model\HashAndRoles;
use Flownative\TokenAuthentication\Security\Repository\HashAndRolesRepository;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Property\TypeConverter\DateTimeConverter;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Utility\Algorithms;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;

/**
 * ApiKeys Controller
 * @Flow\Scope("singleton")
 */
class TokenAuthManagerController extends AbstractModuleController
{
    private const EXPIRATION_PRESETS_IN_DAYS = [7, 30, 60, 90];

    /**
     * @var FusionView
     */
    protected $defaultViewObjectName = FusionView::class;

    #[Flow\Inject]
    protected HashAndRolesRepository $hashAndRolesRepository;

    #[Flow\Inject]
    protected PolicyService $policyService;

    #[Flow\Inject]
    protected Translator $translator;

    /**
     * @var array<string, bool>
     */
    #[Flow\InjectConfiguration(path: 'allowedRoles')]
    protected array $allowedRoles = [];

    /**
     * Sets the Fusion path pattern on the view to avoid conflicts with the frontend fusion
     *
     * This is not needed if your package does not register itself to `Neos.Neos.fusion.autoInclude.*`
     */
    protected function initializeView(ViewInterface $view): void
    {
        parent::initializeView($view);
        $view->setFusionPathPattern('resource://VentusForge.Neos.TokenAuthManager/Private/Fusion/Backend');
    }

    /**
     * @return void
     */
    public function indexAction(): void
    {
        $this->view->assign('tokens', $this->hashAndRolesRepository->findAll());
    }

    public function removeAction(HashAndRoles $token): void
    {
        $this->hashAndRolesRepository->remove($token);

        $this->addFlashMessage($this->translate('flash.tokenDeleted'));

        $this->redirect('index');
    }

    public function createAction(): void
    {
        $this->view->assignMultiple([
            'availableRoles' => $this->getSelectableRoles(),
            'expirationOptions' => $this->getExpirationOptions(),
        ]);
    }

    public function initializeAddAction(): void
    {
        $this->initializeExpiresAtArgument();
    }

    public function addAction(
        array $roleIdentifiers,
        string $expirationPreset,
        ?string $label = null,
        ?\DateTime $expiresAt = null
    ): void {
        $this->assertCustomExpirationIsValid($expirationPreset, $expiresAt, 'create');
        $resolvedExpiresAt = $this->resolveExpiresAt($expirationPreset, $expiresAt);
        $roleIdentifiers = $this->filterAllowedRoleIdentifiers($roleIdentifiers);

        $token = Algorithms::generateRandomString(64);
        $hashAndRoles = HashAndRoles::create($token, $roleIdentifiers, [], $label, $resolvedExpiresAt);
        $this->hashAndRolesRepository->add($hashAndRoles);

        $this->addFlashMessage($this->translate('flash.tokenCreated'));

        $this->redirect('index');
    }

    public function editAction(HashAndRoles $token): void
    {
        $this->view->assign('token', $token);
    }

    public function updateAction(HashAndRoles $token): void
    {
        $this->hashAndRolesRepository->update($token);

        $this->addFlashMessage($this->translate('flash.tokenUpdated'));

        $this->redirect('index');
    }

    public function renewAction(HashAndRoles $token): void
    {
        $this->view->assignMultiple([
            'token' => $token,
            'expirationOptions' => $this->getExpirationOptions(),
        ]);
    }

    public function initializeDoRenewAction(): void
    {
        $this->initializeExpiresAtArgument();
    }

    public function doRenewAction(
        HashAndRoles $token,
        string $expirationPreset,
        ?\DateTime $expiresAt = null
    ): void {
        $this->assertCustomExpirationIsValid($expirationPreset, $expiresAt, 'renew', $token);
        $resolvedExpiresAt = $this->resolveExpiresAt($expirationPreset, $expiresAt);

        $renewedToken = HashAndRoles::create(
            Algorithms::generateRandomString(64),
            $token->getRoles(),
            $token->getSettings(),
            $token->getLabel(),
            $resolvedExpiresAt
        );

        $this->hashAndRolesRepository->add($renewedToken);
        $this->hashAndRolesRepository->remove($token);

        $this->addFlashMessage($this->translate('flash.tokenRenewed'));

        $this->redirect('index');
    }

    /**
     * @return array<string, Role>
     */
    private function getSelectableRoles(): array
    {
        return array_filter(
            $this->policyService->getRoles(),
            fn (Role $role): bool => $this->isRoleAllowed($role->getIdentifier())
        );
    }

    /**
     * @param string[] $roleIdentifiers
     * @return string[]
     */
    private function filterAllowedRoleIdentifiers(array $roleIdentifiers): array
    {
        $allowedRoleIdentifiers = [];
        $disallowedRoleIdentifiers = [];

        foreach ($roleIdentifiers as $roleIdentifier) {
            if ($this->isRoleAllowed($roleIdentifier)) {
                $allowedRoleIdentifiers[] = $roleIdentifier;
                continue;
            }

            $disallowedRoleIdentifiers[] = $roleIdentifier;
        }

        if ($disallowedRoleIdentifiers !== []) {
            $this->addFlashMessage(
                $this->translate('flash.disallowedRolesFiltered', [implode(', ', $disallowedRoleIdentifiers)]),
                '',
                Message::SEVERITY_WARNING
            );
        }

        return $allowedRoleIdentifiers;
    }

    private function isRoleAllowed(string $roleIdentifier): bool
    {
        return ($this->allowedRoles[$roleIdentifier] ?? false) === true;
    }

    /**
     * @return array<int, array{value: string, label: string, date: string}>
     */
    private function getExpirationOptions(): array
    {
        $dateFormat = $this->translate('date.format');
        $options = [];

        foreach (self::EXPIRATION_PRESETS_IN_DAYS as $days) {
            $date = (new \DateTime('today'))->modify('+' . $days . ' days');
            $options[] = [
                'value' => (string) $days,
                'label' => $this->translate('expiration.days', [$days]),
                'date' => $date->format($dateFormat),
            ];
        }

        return $options;
    }

    private function initializeExpiresAtArgument(): void
    {
        if (!$this->arguments->hasArgument('expiresAt')) {
            return;
        }

        if ($this->request->hasArgument('expiresAt')) {
            $expiresAt = $this->request->getArgument('expiresAt');
            if (!empty($expiresAt) && is_string($expiresAt)) {
                $this->request->setArgument('expiresAt', [
                    'date' => $expiresAt,
                    'dateFormat' => 'Y-m-d',
                    'hour' => 23,
                    'minute' => 59,
                    'second' => 59,
                ]);
            }
        }

        $this->arguments->getArgument('expiresAt')->getPropertyMappingConfiguration()
            ->setTypeConverterOption(
                DateTimeConverter::class,
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'Y-m-d'
            );
    }

    private function assertCustomExpirationIsValid(
        string $expirationPreset,
        ?\DateTime $expiresAt,
        string $errorRedirectAction,
        ?HashAndRoles $token = null
    ): void {
        if ($expirationPreset !== 'custom' || $expiresAt !== null) {
            return;
        }

        $this->addFlashMessage($this->translate('flash.customExpirationRequired'), '', Message::SEVERITY_ERROR);
        $this->redirect($errorRedirectAction, null, null, $token instanceof HashAndRoles ? ['token' => $token] : []);
    }

    private function resolveExpiresAt(string $expirationPreset, ?\DateTime $expiresAt): ?\DateTime
    {
        if ($expirationPreset === 'custom' && $expiresAt instanceof \DateTime) {
            return $this->expirationDateOn($expiresAt);
        }

        if ($expirationPreset === 'none') {
            return null;
        }

        $days = (int) $expirationPreset;
        if (in_array($days, self::EXPIRATION_PRESETS_IN_DAYS, true)) {
            return $this->expirationDateOn((new \DateTime('today'))->modify('+' . $days . ' days'));
        }

        return null;
    }

    private function expirationDateOn(\DateTime $date): \DateTime
    {
        $date->setTime(23, 59, 59);

        return $date;
    }

    private function translate(string $id, array $arguments = []): string
    {
        return $this->translator->translateById(
            $id,
            $arguments,
            null,
            null,
            'Main',
            'VentusForge.Neos.TokenAuthManager'
        ) ?: $id;
    }
}
