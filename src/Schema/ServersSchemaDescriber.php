<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Schema;

use Contao\Model\Collection;
use Contao\PageModel;
use Cowegis\Core\Schema\SchemaBuilder;
use Cowegis\Core\Schema\SchemaDescriber;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Server;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function assert;

final class ServersSchemaDescriber implements SchemaDescriber
{
    public function __construct(
        private readonly RepositoryManager $repositoryManager,
        private readonly RequestStack $requestStack,
        private readonly string $baseUri,
    ) {
    }

    public function describe(SchemaBuilder $builder): void
    {
        $repository = $this->repositoryManager->getRepository(PageModel::class);
        assert($repository instanceof ContaoRepository);

        $rootPages = $repository->findPublishedRootPages();
        if (! $rootPages instanceof Collection) {
            return;
        }

        $added = [];

        foreach ($rootPages as $rootPage) {
            assert($rootPage instanceof PageModel);

            $key = $rootPage->dns . '.' . $rootPage->useSSL;

            if (isset($added[$key])) {
                continue;
            }

            $added[$key] = true;
            $dns         = $rootPage->dns !== '' ? $rootPage->dns : $this->getCurrentHost();

            /** @psalm-suppress RedundantCastGivenDocblockType */
            $url = ((bool) $rootPage->useSSL ? 'https://' : 'http://') . $dns . '/' . $this->baseUri;

            $builder->withServers(Server::create()->url($url));
        }
    }

    private function getCurrentHost(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (! $request instanceof Request) {
            return '';
        }

        return $request->getHost();
    }
}
