<?php
namespace Newerton\Yii2Boleto\Boleto\Banco;

use Newerton\Yii2Boleto\Boleto\AbstractBoleto;
use Newerton\Yii2Boleto\CalculoDV;
use Newerton\Yii2Boleto\Contracts\Boleto\Boleto as BoletoContract;
use Newerton\Yii2Boleto\Util;

/**
 * Class Bancoob
 * @package Newerton\Yii2Boleto\Boleto\Banco
 */
class Bancoob extends AbstractBoleto implements BoletoContract
{
    /**
     * Bancoob constructor.
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->addCampoObrigatorio('convenio');
    }

    /**
     * Código do banco
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_BANCOOB;

    /**
     * Define as carteiras disponíveis para este banco
     * @var array
     */
    protected $carteiras = ['1','3'];

    /**
     * Espécie do documento, coódigo para remessa
     * @var string
     */
    protected $especiesCodigo = [
        'DM' => '01',
        'NP' => '02',
        'DS' => '12',
    ];

    /**
     * Define o número do convênio (4, 6 ou 7 caracteres)
     *
     * @var string
     */
    protected $convenio;

    /**
     * Define o número do convênio. Sempre use string pois a quantidade de caracteres é validada.
     *
     * @param  string $convenio
     * @return Bancoob
     */
    public function setConvenio($convenio)
    {
        $this->convenio = $convenio;
        return $this;
    }

    /**
     * Retorna o número do convênio
     *
     * @return string
     */
    public function getConvenio()
    {
        return $this->convenio;
    }

    /**
     * Gera o Nosso Número.
     *
     * @throws \Exception
     * @return string
     */
    protected function gerarNossoNumero()
    {
        return $this->getNumero()
            . CalculoDV::bancoobNossoNumero($this->getAgencia(), $this->getConvenio(), $this->getNumero());
    }

    /**
     * Método que retorna o nosso numero usado no boleto. alguns bancos possuem algumas diferenças.
     *
     * @return string
     */
    public function getNossoNumeroBoleto()
    {
        return substr_replace($this->getNossoNumero(), '-', -1, 0);
    }

    /**
     * Método para gerar o código da posição de 20 a 44
     *
     * @return string
     * @throws \Exception
     */
    protected function getCampoLivre()
    {
        if ($this->campoLivre) {
            return $this->campoLivre;
        }

        $nossoNumero = $this->getNossoNumero();

        $campoLivre = Util::numberFormatGeral($this->getCarteira(), 1);
        $campoLivre .= Util::numberFormatGeral($this->getAgencia(), 4);
        $campoLivre .= Util::numberFormatGeral($this->getCarteira(), 2);
        $campoLivre .= Util::numberFormatGeral($this->getConvenio(), 7);
        $campoLivre .= Util::numberFormatGeral($nossoNumero, 8);
        $campoLivre .= Util::numberFormatGeral(1, 3); //Numero da parcela - Não implementado

        return $this->campoLivre = $campoLivre;
    }
}
