<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\FilesModel;
use Contao\Model;
use Contao\StringUtil;
use Cowegis\Core\Filter\Filter;
use Cowegis\Core\Filter\FilterFactory;
use Netzmacht\Contao\Toolkit\Controller\Hybrid\AbstractHybridController;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Netzmacht\Contao\Toolkit\Response\ResponseTagger;
use Netzmacht\Contao\Toolkit\Routing\RequestScopeMatcher;
use Netzmacht\Contao\Toolkit\View\Template\TemplateRenderer;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_merge;
use function assert;

abstract class MapFragmentAction extends AbstractHybridController
{
    private FilterFactory $filterFactory;

    private UriFactoryInterface $uriFactory;

    private RepositoryManager $repositoryManager;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        FilterFactory $filterFactory,
        UriFactoryInterface $uriFactory,
        RepositoryManager $repositoryManager,
        TemplateRenderer $templateRenderer,
        RequestScopeMatcher $scopeMatcher,
        ResponseTagger $responseTagger,
        RouterInterface $router,
        TranslatorInterface $translator,
        TokenChecker $tokenChecker,
        Adapter $inputAdapter
    ) {
        parent::__construct(
            $templateRenderer,
            $scopeMatcher,
            $responseTagger,
            $router,
            $translator,
            $tokenChecker,
            $inputAdapter
        );

        $this->filterFactory     = $filterFactory;
        $this->uriFactory        = $uriFactory;
        $this->repositoryManager = $repositoryManager;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function prepareTemplateData(array $data, Request $request, Model $model): array
    {
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

    private function createFilter(Request $request): Filter
    {
        $uri      = $this->uriFactory->createUri($request->getUri());
        $autoItem = null;

        if ($this->inputAdapter) {
            $autoItem = $this->inputAdapter->get('auto_item');
        }

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
