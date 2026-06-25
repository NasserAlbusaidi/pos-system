<?php

namespace App\Logging;

use App\Support\PiiMasker;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class PiiMaskingProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;

        if (isset($context['phone']) && is_string($context['phone'])) {
            $context['phone'] = PiiMasker::phone($context['phone']);
        }
        if (isset($context['email']) && is_string($context['email'])) {
            $context['email'] = PiiMasker::email($context['email']);
        }
        if (isset($context['ip']) && is_string($context['ip'])) {
            $context['ip'] = PiiMasker::ip($context['ip']);
        }

        return $record->with(context: $context);
    }
}
