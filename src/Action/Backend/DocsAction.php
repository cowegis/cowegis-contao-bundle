<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action\Backend;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class DocsAction
{
    public function __construct(private readonly Environment $twig, private readonly RouterInterface $router)
    {
    }

    public function __invoke(Request $request): Response
    {
        return new Response(
            $this->twig->render(
                '@CowegisContao/backend/docs.html.twig',
                [
                    'schemaUri' => $this->router->generate('cowegis_api_docs_schema'),
                    'backUrl'   => $this->router->generate(
                        'contao_backend',
                        ['do' => 'cowegis_map', 'ref' => $request->attributes->get('_contao_referer_id')],
                    ),
                ],
            ),
        );
    }
}
