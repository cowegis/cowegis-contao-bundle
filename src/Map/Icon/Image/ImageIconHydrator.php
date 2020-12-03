<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon\Image;

use Contao\File;
use Contao\FilesModel;
use Cowegis\Bundle\Contao\Map\Icon\IconTypeHydrator;
use Cowegis\Bundle\Contao\Model\IconModel;
use Cowegis\Core\Definition\Icon\Icon;
use Cowegis\Core\Definition\Icon\ImageIcon;
use Cowegis\Core\Definition\Options;
use Cowegis\Core\Definition\Point;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

use function round;

final class ImageIconHydrator extends IconTypeHydrator
{
    /** @var RepositoryManager */
    private $repositoryManager;

    public function __construct(RepositoryManager $repositoryManager)
    {
        $this->repositoryManager = $repositoryManager;
    }

    protected function supportedType(): string
    {
        return 'image';
    }

    protected function hydrateIcon(IconModel $iconModel, Icon $icon): void
    {
        $options = $icon->options();

        $this->hydrateIconImage($options, $iconModel->iconImage);
        $this->hydrateImage($options, 'shadow', $iconModel->shadowImage);
        $this->hydrateFileOption($options, 'iconRetinaUrl', $iconModel->iconRetinaImage);
        $this->hydrateFileOption($options, 'shadowRetinaUrl', $iconModel->shadowRetinaImage);
    }

    protected function hydrateFileOption(Options $options, string $option, ?string $uuid): void
    {
        $fileModel = $this->fetchFileModel($uuid);
        if (! $fileModel) {
            return;
        }

        $options->set($option, $fileModel->path);
    }

    protected function hydrateIconImage(Options $options, ?string $uuid): void
    {
        $fileModel = $this->fetchFileModel($uuid);
        if (! $fileModel) {
            return;
        }

        $file = $this->hydrateImage($options, 'icon', $uuid);
        if (! $file || $options->has('popupAnchor')) {
            return;
        }

        $options->set('popupAnchor', new Point(0, -$file->height));
    }

    protected function hydrateImage(Options $options, string $key, ?string $uuid): ?File
    {
        $fileModel = $this->fetchFileModel($uuid);
        if (! $fileModel) {
            return null;
        }

        $file = new File($fileModel->path);

        $options->set($key . 'Url', $fileModel->path);
        $options->set($key . 'Size', new Point($file->width, $file->height));

        if (! $options->has($key . 'Anchor')) {
            $options->set($key . 'Anchor', new Point((int) round($file->width / 2), $file->height));
        }

        return $file;
    }

    protected function fetchFileModel(?string $uuid): ?FilesModel
    {
        if (! $uuid) {
            return null;
        }

        $repository = $this->repositoryManager->getRepository(FilesModel::class);

        return $repository->findByUuid($uuid);
    }

    protected function supportedDefinition(): string
    {
        return ImageIcon::class;
    }
}
