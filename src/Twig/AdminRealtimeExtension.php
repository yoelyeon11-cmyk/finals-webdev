<?php

namespace App\Twig;

use App\Service\AdminRealtimeHelper;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class AdminRealtimeExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly AdminRealtimeHelper $realtimeHelper,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'admin_websocket_url' => $this->realtimeHelper->websocketUrl(),
        ];
    }
}
