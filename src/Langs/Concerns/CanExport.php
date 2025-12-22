<?php

namespace Laravel\Wayfinder\Langs\Concerns;

trait CanExport
{
    protected $export = false;

    protected $exportDefault = false;

    public function export($export = true)
    {
        $this->export = $export;

        return $this;
    }

    public function exportDefault($export = true)
    {
        $this->exportDefault = $export;

        return $this;
    }

    public function exportFormatted(): string
    {
        if ($this->exportDefault) {
            return 'export default ';
        }

        return $this->export ? 'export ' : '';
    }
}
