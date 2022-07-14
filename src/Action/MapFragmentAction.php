<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model;
use Contao\StringUtil;
use Cowegis\Core\Filter\Filter;
use Cowegis\Core\Filter\FilterFactory;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_merge;
use function assert;
use function strtoupper;

abstract class MapFragmentAction extends FragmentAction
{
    /** @var RouterInterface */
    protected $router;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ScopeMatcher */
    protected $scopeMatcher;

    /** @var FilterFactory */
    private $filterFactory;

    /** @var UriFactoryInterface */
    private $uriFactory;

    /** @var RepositoryManager */
    private $repositoryManager;

    /** @var Adapter<Input> */
    private $inputAdapter;

    /** @param Adapter<Input> $inputAdapter */
    public function __construct(
        RouterInterface $router,
        FilterFactory $filterFactory,
        UriFactoryInterface $uriFactory,
        TranslatorInterface $translator,
        ScopeMatcher $scopeMatcher,
        ContaoFramework $contaoFramework,
        RepositoryManager $repositoryManager,
        Adapter $inputAdapter
    ) {
        parent::__construct($contaoFramework);

        $this->router            = $router;
        $this->translator        = $translator;
        $this->scopeMatcher      = $scopeMatcher;
        $this->filterFactory     = $filterFactory;
        $this->uriFactory        = $uriFactory;
        $this->repositoryManager = $repositoryManager;
        $this->inputAdapter      = $inputAdapter;
    }

    protected function getType(): string
    {
        return 'cowegis_map';
    }

    /** {@inheritDoc} */
    protected function renderResponse(
        Request $request,
        Model $model,
        string $templateName,
        string $section,
        array $classes = []
    ): Response {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->getBackendResponse($model);
        }

        return parent::renderResponse($request, $model, $templateName, $section, $classes);
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getTemplateData(Model $model, Request $request, string $section, array $classes = []): array
    {
        $data = parent::getTemplateData($model, $request, $section, $classes);

        $data['mapStyle']     = $this->compileMapStyle($model);
        $data['definitionId'] = $this->getIdentifier($model, $this->getDefaultIdentifier($model));
        $data['clientJs']     = $this->getClientJs($model);

        try {
            $data['mapUri'] = $this->router->generate(
                'cowegis_api_map',
                array_merge(
                    $this->createFilter($request)->toQuery()->toArray(),
                    ['mapId' => $model->cowegis_map, '_locale' => $GLOBALS['TL_LANGUAGE']]
                )
            );
        } catch (InvalidParameterException $exception) {
        }

        return $data;
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    protected function compileMapStyle(Model $model): string
    {
        $style  = '';
        $height = StringUtil::deserialize($model->cowegis_height, true);
        $width  = StringUtil::deserialize($model->cowegis_width, true);

        if (! empty($width['value'])) {
            $style .= 'width:' . $width['value'] . $width['unit'] . ';';
        }

        if (! empty($height['value'])) {
            $style .= 'height:' . $height['value'] . $height['unit'] . ';';
        }

        return $style;
    }

    abstract protected function getIdentifier(Model $model, ?string $identifier): string;

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    private function getDefaultIdentifier(Model $model): ?string
    {
        if ($model->cowegis_map_cssId) {
            return (string) $model->cowegis_map_cssId;
        }

        $cssId = StringUtil::deserialize($model->cssID, true);
        if (! empty($cssId[0])) {
            return 'map_' . $cssId[0];
        }

        return null;
    }

    private function getBackendResponse(Model $module): Response
    {
        $href = $this->router->generate(
            'contao_backend',
            ['do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $module->id]
        );

        $name = $this->translator->trans('FMD.' . $this->getType() . '.0', [], 'contao_modules');

        $template = $this->contaoFramework->createInstance(FrontendTemplate::class, ['be_wildcard']);
        assert($template instanceof FrontendTemplate);
        $template->setData(
            [
                'wildcard' => '### ' . strtoupper($name) . ' ###',
                'id'       => $module->id,
                'link'     => $module->name,
                'href'     => $href,
            ]
        );

        return $template->getResponse();
    }

    private function createFilter(Request $request): Filter
    {
        $uri      = $this->uriFactory->createUri($request->getUri());
        $autoItem = $this->inputAdapter->get('auto_item');

        if ($autoItem) {
            $query = $uri->getQuery();
            $uri   = $uri->withQuery(($query ? '&' : '') . 'auto_item=' . $autoItem);
        }

        return $this->filterFactory->createFromUri($uri);
    }

    private function getClientJs(Model $model): ?string
    {
        switch ($model->cowegis_client) {
            case 'client':
                return 'bundles/cowegisclient/js/cowegis.js';

            case 'custom':
                $repository = $this->repositoryManager->getRepository(FilesModel::class);
                assert($repository instanceof ContaoRepository);
                $model = $repository->findOneByUuid($model->cowegis_client_custom);

                return $model ? $model->path : null;
        }

        return null;
    }
}
