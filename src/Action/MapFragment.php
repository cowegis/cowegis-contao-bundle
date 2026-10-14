<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\CoreBundle\Controller\AbstractFragmentController;
use Contao\FilesModel;
use Contao\Input;
use Contao\Model;
use Contao\StringUtil;
use Cowegis\Core\Filter\Filter;
use Cowegis\Core\Filter\FilterFactory;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\InvalidParameterException;

use function array_merge;
use function assert;
use function is_string;

/** @psalm-require-extends AbstractFragmentController */
trait MapFragment
{
    public function __construct(
        private readonly FilterFactory $filterFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly RepositoryManager $repositoryManager,
        private readonly Packages $packages,
    ) {
    }

    abstract protected function getIdentifier(Model $model, string|null $identifier): string;

    /**
     * @param array<string,mixed> $data
     *
     * @return array<string,mixed>
     */
    protected function buildTemplateData(array $data, Request $request, Model $model): array
    {
        $data['mapStyle']   = $this->compileMapStyle($model);
        $data['mapId']      = $this->getIdentifier($model, $this->getDefaultIdentifier($model));
        $data['clientJs']   = $this->getClientJs($model);
        $data['searchable'] = false;

        try {
            $data['mapUri'] = $this->generateUrl(
                'cowegis_api_map',
                array_merge(
                    $this->createFilter($request)->toQuery()->toArray(),
                    ['mapId' => $model->cowegis_map, '_locale' => $GLOBALS['TL_LANGUAGE']],
                ),
            );
        } catch (InvalidParameterException) {
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

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    private function getDefaultIdentifier(Model $model): string|null
    {
        if ($model->cowegis_mapId) {
            return (string) $model->cowegis_mapId;
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
        $autoItem = $this->getContaoAdapter(Input::class)->get('auto_item');

        if (is_string($autoItem) && $autoItem !== '') {
            $query = $uri->getQuery();
            $uri   = $uri->withQuery(($query ? '&' : '') . 'auto_item=' . $autoItem);
        }

        return $this->filterFactory->createFromUri($uri);
    }

    private function getClientJs(Model $model): string|null
    {
        switch ($model->cowegis_client) {
            case 'client':
                return $this->packages->getUrl('cowegis/cowegis.js', 'cowegis');

            case 'custom':
                $repository = $this->repositoryManager->getRepository(FilesModel::class);
                assert($repository instanceof ContaoRepository);
                /** @psalm-suppress UndefinedMagicMethod */
                $model = $repository->findOneByUuid($model->cowegis_client_custom);

                return $model ? $model->path : null;
        }

        return null;
    }
}
