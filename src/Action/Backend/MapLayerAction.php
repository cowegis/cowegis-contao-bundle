<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action\Backend;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model;
use Contao\RequestToken;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerRepository;
use Cowegis\Bundle\Contao\Model\Map\MapModel;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use function time;

final class MapLayerAction
{
    /** @var ContaoFramework */
    private $framework;

    /** @var RouterInterface */
    private $router;
    /**
     * @var RepositoryManager
     */
    private $repositoryManager;

    public function __construct(
        ContaoFramework $framework,
        RepositoryManager $repositoryManager,
        RouterInterface $router
    ) {
        $this->framework         = $framework;
        $this->router            = $router;
        $this->repositoryManager = $repositoryManager;
    }

    public function __invoke(int $mapId, int $layerId, Request $request) : Response
    {
        $this->framework->initialize();

        // TODO: Guard user have access to the map

        /** @var MapModel $mapModel */
        /** @var LayerModel $layerModel */
        $mapModel   = $this->fetchModel(MapModel::class, $mapId);
        $layerModel = $this->fetchModel(LayerModel::class, $layerId);

        switch ($request->request->get('action')) {
            case 'activate':
                $this->activateLayer($mapModel, $layerModel);
                break;

            case 'disable':
                $this->disableLayer($mapModel, $layerModel);
                break;

            case 'toggleVisibility':
                $this->toggleVisibility($mapModel, $layerModel);
                break;

            default:
                throw new BadRequestHttpException();
        }

        return new RedirectResponse(
            $this->router->generate(
                'contao_backend',
                // TODO: Add ref
                ['do' => 'cowegis_map', 'table' => 'tl_cowegis_layer', 'id' => $mapId, 'rt' => RequestToken::get()]
            )
        );
    }

    private function activateLayer(MapModel $mapModel, LayerModel $layerModel) : void
    {
        /** @var MapLayerRepository $repository */
        $repository = $this->repositoryManager->getRepository(MapLayerModel::class);
        $model      = $repository->findLayer($mapModel->id(), $layerModel->id());

        if ($model === null) {
            $model = new MapLayerModel();
            $model->setRow(
                [
                    'pid'     => $mapModel->id(),
                    'layerId' => $layerModel->id(),
                ]
            );
        }

        $model->tstamp         = time();
        $model->active         = '1';
        $model->initialVisible = '1';
        // Fixme: Do not use repository to save it. It might get translated sometimes
        $repository->save($model);

        $layerRepository = $this->repositoryManager->getRepository(LayerModel::class);
        $collection      = $layerRepository->findBy(['.pid=?'], [$layerModel->id()]) ?: [];

        foreach ($collection as $child) {
            $this->activateLayer($mapModel, $child);
        }
    }

    private function disableLayer(MapModel $mapModel, LayerModel $layerModel) : void
    {
        /** @var MapLayerRepository $repository */
        $repository = $this->repositoryManager->getRepository(MapLayerModel::class);
        $model      = $repository->findLayer($mapModel->id(), $layerModel->id());
        if ($model === null) {
            return;
        }

        $model->tstamp = time();
        $model->active = '';
        // Fixme: Do not use repository to save it. It might get translated sometimes
        $repository->save($model);
    }

    private function toggleVisibility(MapModel $mapModel, LayerModel $layerModel) : void
    {
        /** @var MapLayerRepository $repository */
        $repository = $this->repositoryManager->getRepository(MapLayerModel::class);
        $model      = $repository->findLayer($mapModel->id(), $layerModel->id());
        if ($model === null) {
            throw new BadRequestHttpException();
        }

        $model->tstamp         = time();
        $model->initialVisible = $model->initialVisible > 0 ? 0 : 1;
        // Fixme: Do not use repository to save it. It might get translated sometimes
        $repository->save($model);
    }

    private function fetchModel(string $modelClass, int $modelId) : Model
    {
        $repository = $this->repositoryManager->getRepository($modelClass);
        $model      = $repository->find($modelId);
        if (!$model instanceof $modelClass) {
            throw new BadRequestHttpException();
        }

        return $model;
    }
}
