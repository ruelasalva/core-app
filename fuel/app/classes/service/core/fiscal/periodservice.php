<?php

/**
 * SERVICE CORE_FISCAL_PERIODSERVICE
 *
 * Administra estados de periodos fiscales.
 *
 * @package  app
 */
class Service_Core_Fiscal_PeriodService
{
    protected $valid_statuses = ['open', 'locked', 'closed'];

    /**
     * OPEN
     *
     * REABRE O CREA UN PERIODO FISCAL.
     *
     * @access  public
     * @return  Array
     */
    public function open($rfc, $period, $user_id = 0)
    {
        return $this->set_status($rfc, $period, 'open', $user_id);
    }

    /**
     * LOCK
     *
     * BLOQUEA UN PERIODO FISCAL SIN CERRARLO.
     *
     * @access  public
     * @return  Array
     */
    public function lock($rfc, $period, $user_id = 0)
    {
        return $this->set_status($rfc, $period, 'locked', $user_id);
    }

    /**
     * CLOSE
     *
     * CIERRA UN PERIODO FISCAL.
     *
     * @access  public
     * @return  Array
     */
    public function close($rfc, $period, $user_id = 0)
    {
        return $this->set_status($rfc, $period, 'closed', $user_id);
    }

    /**
     * ASSERT REBUILD ALLOWED
     *
     * EVITA RECONSTRUIR LIBRO FISCAL EN PERIODOS CERRADOS.
     *
     * @access  public
     * @return  Void
     */
    public function assert_rebuild_allowed($rfc, $period)
    {
        $period_row = $this->find($rfc, $period);
        if ($period_row && in_array((string) $period_row['status'], ['locked', 'closed'], true)) {
            throw new \RuntimeException('El periodo fiscal '.$period.' del RFC '.$this->normalize_rfc($rfc).' esta '.(string) $period_row['status'].'. No se permite reconstruir el libro fiscal.');
        }
    }

    /**
     * FIND
     *
     * BUSCA PERIODO FISCAL.
     *
     * @access  public
     * @return  Array|null
     */
    public function find($rfc, $period)
    {
        $this->validate_schema();
        $rfc = $this->normalize_rfc($rfc);
        $period = $this->normalize_period($period);

        $row = \DB::select()
            ->from('core_fiscal_periods')
            ->where('taxpayer_rfc', '=', $rfc)
            ->where('period_key', '=', $period)
            ->execute()
            ->current();

        return $row ?: null;
    }

    /**
     * SET STATUS
     *
     * CAMBIA ESTADO DEL PERIODO.
     *
     * @access  protected
     * @return  Array
     */
    protected function set_status($rfc, $period, $status, $user_id)
    {
        $this->validate_schema();
        $rfc = $this->normalize_rfc($rfc);
        $period = $this->normalize_period($period);
        $status = $this->normalize_status($status);
        $now = time();

        $period_id = $this->find_or_create_period($rfc, $period, $now);
        $set = [
            'status' => $status,
            'updated_at' => $now,
        ];

        if ($status === 'open') {
            $set['locked_by'] = 0;
            $set['locked_at'] = 0;
            $set['closed_by'] = 0;
            $set['closed_at'] = 0;
        } elseif ($status === 'locked') {
            $set['locked_by'] = (int) $user_id;
            $set['locked_at'] = $now;
        } elseif ($status === 'closed') {
            $set['closed_by'] = (int) $user_id;
            $set['closed_at'] = $now;
        }

        \DB::update('core_fiscal_periods')
            ->set($set)
            ->where('id', '=', (int) $period_id)
            ->execute();

        \Log::info('Fiscal Period: RFC='.$rfc.' periodo='.$period.' status='.$status.' user='.(int) $user_id);

        return [
            'id' => (int) $period_id,
            'rfc' => $rfc,
            'period' => $period,
            'status' => $status,
        ];
    }

    /**
     * FIND OR CREATE PERIOD
     *
     * CREA PERIODO SI NO EXISTE.
     *
     * @access  protected
     * @return  Int
     */
    protected function find_or_create_period($rfc, $period, $now)
    {
        $row = $this->find($rfc, $period);
        if ($row) {
            return (int) $row['id'];
        }

        $dates = $this->period_dates($period);
        list($year, $month) = explode('-', $period);

        $insert = \DB::insert('core_fiscal_periods')->set([
            'company_id' => $this->company_id($rfc),
            'taxpayer_rfc' => $rfc,
            'fiscal_year' => (int) $year,
            'fiscal_month' => (int) $month,
            'period_key' => $period,
            'date_from' => $dates['from'],
            'date_to' => $dates['to'],
            'status' => 'open',
            'locked_by' => 0,
            'locked_at' => 0,
            'closed_by' => 0,
            'closed_at' => 0,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        return (int) $insert[0];
    }

    protected function validate_schema()
    {
        if (!\DBUtil::table_exists('core_fiscal_periods')) {
            throw new \RuntimeException('Tabla requerida no existe: core_fiscal_periods.');
        }
    }

    protected function normalize_rfc($rfc)
    {
        $rfc = strtoupper(trim((string) $rfc));
        if ($rfc === '' || !preg_match('/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc)) {
            throw new \InvalidArgumentException('RFC invalido para periodo fiscal.');
        }
        return $rfc;
    }

    protected function normalize_period($period)
    {
        $period = trim((string) $period);
        if (!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $period)) {
            throw new \InvalidArgumentException('Periodo invalido. Usa formato YYYY-MM.');
        }
        return $period;
    }

    protected function normalize_status($status)
    {
        $status = trim((string) $status);
        if (!in_array($status, $this->valid_statuses, true)) {
            throw new \InvalidArgumentException('Estado fiscal invalido: '.$status.'.');
        }
        return $status;
    }

    protected function period_dates($period)
    {
        $from = $period.'-01';
        return [
            'from' => $from,
            'to' => date('Y-m-t', strtotime($from)),
        ];
    }

    protected function company_id($rfc)
    {
        if (!\DBUtil::table_exists('core_companies')) {
            return 0;
        }

        $row = \DB::select('id')
            ->from('core_companies')
            ->where('rfc', '=', $rfc)
            ->execute()
            ->current();

        return $row ? (int) $row['id'] : 0;
    }
}
