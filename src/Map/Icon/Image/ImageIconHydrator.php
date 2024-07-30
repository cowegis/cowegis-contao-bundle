<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon\Image;

use Contao\File;
use Contao\FilesModel;
use Contao\StringUtil;
use Cowegis\Bundle\Contao\Map\Icon\IconTypeHydrator;
use Cowegis\Bundle\Contao\Model\IconModel;
use Cowegis\Core\Definition\Icon\Icon;
use Cowegis\Core\Definition\Icon\ImageIcon;
use Cowegis\Core\Definition\Options;
use Cowegis\Core\Definition\Point;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

use function assert;
use function round;

final class ImageIconHydrator extends IconTypeHydrator
{
    public function __construct(private readonly RepositoryManager $repositoryManager)
    {
    }

    protected function supportedType(): string
    {
        return 'image';
    }

    protected function hydrateIcon(IconModel $iconModel, Icon $icon): void
    {
        $options = $icon->options();

        $this->hydrateIconImage($options, $iconModel);
        $this->hydrateImage($options, 'shadow', $iconModel->shadowImage, $iconModel);
        $this->hydrateFileOption($options, 'iconRetinaUrl', $iconModel->iconRetinaImage);
        $this->hydrateFileOption($options, 'shadowRetinaUrl', $iconModel->shadowRetinaImage);
    }

    protected function hydrateFileOption(Options $options, string $option, string|null $uuid): void
    {
        $fileModel = $this->fetchFileModel($uuid);
        if (! $fileModel) {
            return;
        }

        $options->set($option, $fileModel->path);
    }

    protected function hydrateIconImage(Options $options, IconModel $iconModel): void
    {
        $fileModel = $this->fetchFileModel($iconModel->iconImage);
        if (! $fileModel) {
            return;
        }

        $file = $this->hydrateImage($options, 'icon', $iconModel->iconImage, $iconModel);
        if (! $file || $options->has('popupAnchor')) {
            return;
        }

        $options->set('popupAnchor', new Point(0, -$options->get('iconSize')->y()));
    }

    protected function hydrateImage(Options $options, string $key, string|null $uuid, IconModel $iconModel): File|null
    {
        $fileModel = $this->fetchFileModel($uuid);
        if (! $fileModel) {
            return null;
        }

        $file = new File($fileModel->path);

        $options->set($key . 'Url', $fileModel->path);

        if ($iconModel->{$key . 'Size'}) {
            $size     = StringUtil::trimsplit(',', $iconModel->{$key . 'Size'});
            $iconSize = new Point((int) $size[0], (int) $size[1]);
        } else {
            $iconSize = new Point($file->width, $file->height);
        }

        $options->set($key . 'Size', $iconSize);

        if (! $options->has($key . 'Anchor')) {
            if ($iconModel->{$key . 'Anchor'}) {
                $size = StringUtil::trimsplit(',', $iconModel->{$key . 'Anchor'});
                $options->set($key . 'Size', new Point((int) $size[0], (int) $size[1]));
            } else {
                $options->set($key . 'Anchor', new Point((int) round($iconSize->x() / 2), $iconSize->y()));
            }
        }

        return $file;
    }

    protected function fetchFileModel(string|null $uuid): FilesModel|null
    {
        if ($uuid === null) {
            return null;
        }

        $repository = $this->repositoryManager->getRepository(FilesModel::class);
        assert($repository instanceof ContaoRepository);

        return $repository->findByUuid($uuid);
    }

    protected function supportedDefinition(): string
    {
        return ImageIcon::class;
    }
}
