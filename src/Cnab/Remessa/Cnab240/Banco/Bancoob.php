<?php
namespace Newerton\Yii2Boleto\Cnab\Remessa\Cnab240\Banco;

use Newerton\Yii2Boleto\CalculoDV;
use Newerton\Yii2Boleto\Cnab\Remessa\Cnab240\AbstractRemessa;
use Newerton\Yii2Boleto\Contracts\Cnab\Remessa as RemessaContract;
use Newerton\Yii2Boleto\Contracts\Boleto\Boleto as BoletoContract;
use Newerton\Yii2Boleto\Util;

/**
 * Class Bancoob
 * @package Newerton\Yii2Boleto\Cnab\Remessa\Cnab240\Banco
 */
class Bancoob extends AbstractRemessa implements RemessaContract
{
    const ESPECIE_DUPLICATA = '01';
    const ESPECIE_NOTA_PROMISSORIA = '02';
    const ESPECIE_DUPLICATA_SERVICO = '12';

    const OCORRENCIA_REMESSA = '01';
    const OCORRENCIA_PEDIDO_BAIXA = '02';
    const OCORRENCIA_CONCESSAO_ABATIMENTO = '04';
    const OCORRENCIA_CANC_ABATIMENTO_CONCEDIDO = '05';
    const OCORRENCIA_ALT_VENCIMENTO = '06';
    const OCORRENCIA_ALT_SEU_NUMERO = '08';
    const OCORRENCIA_PEDIDO_PROTESTO = '09';
    const OCORRENCIA_SUSTAR_PROTESTO = '10';
    const OCORRENCIA_DISPENSAR_JUROS = '11';
    const OCORRENCIA_ALT_PAGADOR = '12';
    const OCORRENCIA_ALT_OUTROS_DADOS = '31';
    const OCORRENCIA_BAIXAR = '34';

    const INSTRUCAO_SEM = '00';
    const INSTRUCAO_COBRAR_JUROS = '01';
    const INSTRUCAO_NAO_PROTESTAR = '07';
    const INSTRUCAO_PROTESTAR = '09';
    const INSTRUCAO_PROTESTAR_VENC_03 = '03';
    const INSTRUCAO_PROTESTAR_VENC_04 = '04';
    const INSTRUCAO_PROTESTAR_VENC_05 = '05';
    const INSTRUCAO_PROTESTAR_VENC_15 = '15';
    const INSTRUCAO_PROTESTAR_VENC_20 = '20';
    const INSTRUCAO_CONCEDER_DESC_ATE = '22';
    const INSTRUCAO_DEVOLVER_APOS_15 = '42';
    const INSTRUCAO_DEVOLVER_APOS_30 = '43';

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
     *
     * @var string
     */
    protected $codigoBanco = BoletoContract::COD_BANCO_BANCOOB;

    /**
     * Valor Total dos Titulos
     *
     * @var numeric
     */
    protected $valorTotalTitulos = 0;
    
    /**
     * Define as carteiras disponíveis para cada banco
     *
     * @var array
     */
    protected $carteiras = [1];

    /**
     * Caracter de fim de linha
     *
     * @var string
     */
    protected $fimLinha = "\r\n";

    /**
     * Caracter de fim de arquivo
     *
     * @var null
     */
    protected $fimArquivo = "";

    /**
     * Convenio com o banco
     *
     * @var string
     */
    protected $convenio;

    /**
     * Quantidade de registros do lote.
     */
    private $qtyRegistrosLote;
    
    /**
     * @return mixed
     */
    public function getConvenio()
    {
        return $this->convenio;
    }

    /**
     * @param mixed $convenio
     *
     * @return Bancoob
     */
    public function setConvenio($convenio)
    {
        $this->convenio = ltrim($convenio, 0);

        return $this;
    }

    /**
     * Codigo do cliente junto ao banco.
     *
     * @var string
     */
    protected $codigoCliente;

    /**
     * Retorna o codigo do cliente.
     *
     * @return string
     */
    public function getCodigoCliente()
    {
        return $this->codigoCliente;
    }

    /**
     * Seta o codigo do cliente.
     *
     * @param  mixed $codigoCliente
     * @return Santander
     */
    public function setCodigoCliente($codigoCliente)
    {
        $this->codigoCliente = $codigoCliente;

        return $this;
    }
    
    /**
     * Retorna o codigo de transmissão.
     *
     * @return string
     * @throws \Exception
     */
    public function getCodigoTransmissao()
    {
        return Util::formatCnab('9', $this->getAgencia(), 5)
            . CalculoDv::bancoobAgencia($this->getAgencia())
            . Util::formatCnab('9', $this->getConta(), 12)
            . Util::formatCnab('9', $this->getContaDv(), 1);
    }
    
    /**
     * @return $this
     */
    protected function header()
    {
        $this->iniciaHeader();

        $this->add(1, 3, $this->getCodigoBanco());
        $this->add(4, 7, '0000');
        $this->add(8, 8, '0');
        $this->add(9, 17, '');
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? '2' : '1'); // Tipo de inscrição da empresa
        $this->add(19, 32, Util::formatCnab('9L', $this->getBeneficiario()->getNomeDocumento(), 14));
        $this->add(33, 52, Util::formatCnab('X', $this->getConvenio(), 20));
        $this->add(53, 57, Util::formatCnab('9', $this->getAgencia(), 5));
        $this->add(58, 58, CalculoDv::bancoobAgencia($this->getAgencia()));
        $this->add(59, 70, Util::formatCnab('9', $this->getConta(), 12));
        $this->add(71, 71, Util::formatCnab('9', $this->getContaDv(), 1));
        $this->add(72, 72, '');
        $this->add(73, 102, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(103, 132, 'SICOOB');
        $this->add(133, 142, '');
        $this->add(143, 143, '1');
        $this->add(144, 151, date('dmY'));
        $this->add(152, 157, date('His'));
        $this->add(158, 163, Util::formatCnab('9', $this->getIdremessa(), 6));
        $this->add(164, 166, '081');
        $this->add(167, 171, '00000');
        $this->add(172, 191, '');
        $this->add(192, 211, '');
        $this->add(212, 240, '');

        return $this;
    }

    public function addBoleto(BoletoContract $boleto, $nSequencialLote = null)
    {
        
        $this->segmentoP($nSequencialLote + $nSequencialLote + 1, $boleto);
        $this->segmentoQ($nSequencialLote + $nSequencialLote + 2, $boleto);
        $this->segmentoR($nSequencialLote + $nSequencialLote + 2, $boleto);
        $this->segmentoS($nSequencialLote + $nSequencialLote + 2, $boleto);

        return $this;
    }

    /**
     * @param integer $nSequencialLote
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    protected function segmentoP($nSequencialLote, BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //Código do Banco
        $this->add(4, 7, '0001'); // Numero do lote remessa
        $this->add(8, 8, '3'); // Numero do lote remessa
        $this->add(9, 13, Util::formatCnab(9, $nSequencialLote, 5)); // Nº sequencial do registro de lote
        $this->add(14, 14, Util::formatCnab('9', 'P', 1)); // Nº sequencial do registro de lote
        $this->add(15, 15, ''); // Reservado (Uso Banco)
        $this->add(16, 17, '01'); // Código de movimento remessa
        $this->add(18, 22, Util::formatCnab(9, $this->getAgencia(), 5)); // Agência do cedente
        $this->add(23, 23, Util::formatCnab(9, '', 1)); // Digito verificador da Agência do cedente
        $this->add(24, 35, Util::formatCnab(9, $this->getConta(), 12)); // Numero da conta corrente
        $this->add(36, 36, Util::formatCnab('9', $this->getContaDv(), 1));
        $this->add(37, 37, ''); // Reservado (Uso Banco)

        $this->add(38, 47, Util::formatCnab(9, $boleto->getNumero(), 10)); 
        $this->add(48, 49, '01');
        $this->add(50, 51, '01');
        $this->add(52, 52, '4');
        $this->add(53, 57, '');

        $this->add(58, 58, $this->getCarteira()); // Tipo de Cobrança

        $this->add(59, 59, '0'); // Forma de Cadastramento
        $this->add(60, 60, ''); // Tipo de documento
        $this->add(61, 61, '2'); // Reservado (Uso Banco)
        $this->add(62, 62, '2'); // Reservado (Uso Banco)
        //
        $this->add(63, 77, Util::formatCnab(9, $boleto->getNumeroControle(), 15)); // Seu Número
        $this->add(78, 85, $boleto->getDataVencimento()->format('dmY')); // Data de vencimento do título
        $this->add(86, 100, Util::formatCnab(9, $boleto->getValor(), 15, 2)); // Valor nominal do título
        $this->add(101, 105, Util::formatCnab(9, 0, 5)); //Agência encarregada da cobrança
        $this->add(106, 106, '');
        $this->add(107, 108, '99'); //Espécie do título
        $this->add(109, 109, Util::formatCnab('9', 'N', 1)); //Identif. de título Aceito/Não Aceito
        $this->add(110, 117, date('dmY')); //Data da emissão do título

        $juros = 0;
        if ($boleto->getJuros() > 0) {
            $juros = Util::percent($boleto->getValor(), $boleto->getJuros()) / 30;
        }
        $this->add(118, 118, 1); //Código do juros de mora - 1 = Valor fixo ate a data informada – Informar o valor no campo “valor de desconto a ser concedido”.
        $this->add(119, 126, Util::formatCnab(9, $boleto->getDataVencimento()->format('dmY'), 8)); //Data do juros de mora / data de vencimento do titulo
        $this->add(127, 141, Util::formatCnab(9, $juros, 15, 2)); //Valor da mora/dia ou Taxa mensal
        $this->add(142, 142, '0');
        $this->add(143, 150, ''); 
        $this->add(151, 165, Util::formatCnab(9, $boleto->getDesconto(), 15, 2)); //Valor ou Percentual do desconto concedido //TODO
        $this->add(166, 180, Util::formatCnab(9, 0, 15, 2)); //Valor do IOF a ser recolhido
        $this->add(181, 195, Util::formatCnab(9, 0, 15, 2)); //Valor do abatimento
        $this->add(196, 220, ''); //Identificação do título na empresa
        $this->add(221, 221, Util::formatCnab(9, 1, 1)); //Código para protesto
        $this->add(222, 223, Util::formatCnab(9, 0, 2)); //Número de dias para protesto
        $this->add(224, 224, Util::formatCnab(9, 0, 1)); //Código para Baixa/Devolução
        $this->add(225, 227, '');
        $this->add(228, 229, '09'); // Código da moeda
        $this->add(230, 239, '0000000000'); 
        $this->add(240, 240, ''); // Reservado (Uso Banco)

        $this->valorTotalTitulos += $boleto->getValor();
        
        return $this;
    }

    /**
     * @param integer $nSequencialLote
     * @param BoletoContract $boleto
     *
     * @throws \Exception
     */
    public function segmentoQ($nSequencialLote, BoletoContract $boleto)
    {
        $this->qtyRegistrosLote = $nSequencialLote;
        $this->iniciaDetalhe();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //Código do Banco
        $this->add(4, 7, '0001'); // Numero do lote remessa
        $this->add(8, 8, '3'); // Numero do lote remessa
        $this->add(9, 13, Util::formatCnab(9, $nSequencialLote, 5)); // Nº sequencial do registro de lote
        $this->add(14, 14, Util::formatCnab('9', 'Q', 1)); // Nº sequencial do registro de lote
        $this->add(15, 15, ''); // Reservado (Uso Banco)
        $this->add(16, 17, '01'); // Código de movimento remessa
        $this->add(18, 18, '1'); // Tipo de inscrição do sacado
        $this->add(19, 33, Util::formatCnab(9, Util::onlyNumbers($boleto->getPagador()->getDocumento()), 15)); // Número de inscrição do sacado
        $this->add(34, 73, Util::formatCnab('X', $boleto->getPagador()->getNome(), 40)); // Nome do pagador/Sacado
        $this->add(74, 113, Util::formatCnab('X', $boleto->getPagador()->getEndereco(), 40)); // Endereço do pagador/Sacado
        $this->add(114, 128, Util::formatCnab('X', $boleto->getPagador()->getBairro(), 15)); // Bairro do pagador/Sacado
        $this->add(129, 133, Util::formatCnab(9, Util::onlyNumbers($boleto->getPagador()->getCep()), 5)); // CEP do pagador/Sacado
        $this->add(134, 136, Util::formatCnab(9, Util::onlyNumbers(substr($boleto->getPagador()->getCep(), 6, 9)), 3)); //SUFIXO do cep do pagador/Sacado
        $this->add(137, 151, Util::formatCnab('X', $boleto->getPagador()->getCidade(), 15)); // cidade do sacado
        $this->add(152, 153, Util::formatCnab('X', $boleto->getPagador()->getUf(), 2)); // Uf do sacado
        $this->add(154, 154, '1'); // Tipo de inscrição do sacado
        $this->add(155, 169, Util::formatCnab(9, Util::onlyNumbers($boleto->getPagador()->getDocumento()), 15)); // Tipo de inscrição do sacado
        $this->add(170, 209, Util::formatCnab('X', '', 40)); // Nome do Sacador
        $this->add(210, 212, '000'); // Identificador de carne 000 - Não possui, 001 - Possui Carné
        $this->add(213, 232, '');
        $this->add(233, 240, '');
        
        return $this;
    }

    /**
     * @param integer $nSequencialLote
     * @param BoletoContract $boleto
     *
     * @throws \Exception
     */
    public function segmentoR($nSequencialLote, BoletoContract $boleto)
    {
        $this->qtyRegistrosLote = $nSequencialLote;
        $this->iniciaDetalhe();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //Código do Banco
        $this->add(4, 7, '0001'); // Numero do lote remessa
        $this->add(8, 8, '3'); // Numero do lote remessa
        $this->add(9, 13, Util::formatCnab(9, $nSequencialLote, 5)); // Nº sequencial do registro de lote
        $this->add(14, 14, Util::formatCnab('9', 'R', 1)); // Nº sequencial do registro de lote
        $this->add(15, 15, ''); // Reservado (Uso Banco)
        $this->add(16, 17, '01'); // Código de movimento remessa
        $this->add(18, 18, '0');
        $this->add(27, 65, Util::formatCnab(9, 0, 35));
        $this->add(66, 66, '1');
        $this->add(67, 74, Util::formatCnab(9, $boleto->getDataVencimento()->format('dmY'), 8)); //Data do juros de mora / data de vencimento do titulo
        $this->add(75, 89, Util::formatCnab(9, 0, 13));
        $this->add(90, 99, '');
        $this->add(100, 139, '');
        $this->add(140, 179, '');
        $this->add(180, 199, '');
        $this->add(200, 215, Util::formatCnab(9, 0, 16));
        $this->add(216, 216, '');
        $this->add(217, 228, Util::formatCnab(9, 0, 12));
        $this->add(229, 230, '');
        $this->add(231, 231, '0');
        $this->add(232, 240, '');
        
        return $this;
    }

    /**
     * @param integer $nSequencialLote
     * @param BoletoContract $boleto
     *
     * @throws \Exception
     */
    public function segmentoS($nSequencialLote, BoletoContract $boleto)
    {
        $this->qtyRegistrosLote = $nSequencialLote;
        $this->iniciaDetalhe();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //Código do Banco
        $this->add(4, 7, '0001'); // Numero do lote remessa
        $this->add(8, 8, '3'); // Numero do lote remessa
        $this->add(9, 13, Util::formatCnab(9, $nSequencialLote, 5)); // Nº sequencial do registro de lote
        $this->add(14, 14, Util::formatCnab('9', 'S', 1)); // Nº sequencial do registro de lote
        $this->add(15, 15, ''); // Reservado (Uso Banco)
        $this->add(16, 17, '01'); // Código de movimento remessa
        $this->add(18, 18, '3');
        $this->add(19, 240, '');
        
        return $this;
    }
    
    /**
     * @return $this
     */
    protected function trailer()
    {
        $this->iniciaTrailer();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //Codigo do banco
        $this->add(4, 7, '9999'); // Numero do lote remessa
        $this->add(8, 8, '9'); //Tipo de registro
        $this->add(9, 17, ''); // Reservado (Uso Banco)
        $this->add(18, 23, Util::formatCnab(9, 1, 6)); // Qtd de lotes do arquivo
        $this->add(24, 29, Util::formatCnab(9, ($this->qtyRegistrosLote + 4), 6)); // Qtd de lotes do arquivo
        $this->add(30, 35, Util::formatCnab(9, 0, 6));
        $this->add(36, 240, ''); // Reservado (Uso Banco)
        
        return $this;
    }

    protected function headerLote() 
    {
        $this->iniciaHeaderLote();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //Codigo do banco
        $this->add(4, 7, '0001'); // Lote de Serviço
        $this->add(8, 8, '1'); // Tipo de Registro
        $this->add(9, 9, 'R'); // Tipo de operação
        $this->add(10, 11, '01'); // Tipo de serviço
        $this->add(12, 13, ''); // Reservados (Uso Banco)
        $this->add(14, 16, '040'); // Versão do layout
        $this->add(17, 17, ''); // Reservados (Uso Banco)
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? '2' : '1'); // Tipo de inscrição da empresa
        $this->add(19, 33, Util::formatCnab('9L', $this->getBeneficiario()->getDocumento(), 14)); // Numero de inscrição da empresa
        $this->add(34, 53, ''); // Reservados (Uso Banco)        
        $this->add(54, 58, Util::formatCnab(9, $this->getAgencia(), 5)); // Agência do cedente
        $this->add(59, 59, Util::formatCnab(9, '', 1)); // Digito verificador da Agência do cedente
        $this->add(60, 71, Util::formatCnab(9, $this->getConta(), 12)); // Numero da conta corrente
        $this->add(72, 72, Util::formatCnab('9', $this->getContaDv(), 1));        
        $this->add(73, 73, ''); // Reservados (Uso Banco)
        $this->add(74, 103, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30)); // Nome do cedente
        $this->add(104, 143, ''); // Mensagem 1
        $this->add(144, 183, ''); // Mensagem 2
        $this->add(184, 191, Util::formatCnab(9, 0, 8)); // Número Remessa/retorno
        $this->add(192, 199, date('dmY')); // Data de Gravação do arquivo
        $this->add(200, 240, ''); // Reservado (Uso Banco)

        return $this;
    }

    protected function trailerLote() {
        $this->iniciaTrailerLote();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //Codigo do banco
        $this->add(4, 7, '9999'); // Numero do lote remessa
        $this->add(8, 8, '5'); //Tipo de registro
        $this->add(9, 17, ''); // Reservado (Uso Banco)
        $this->add(18, 23, Util::formatCnab(9, 1, 6)); // Qtd de lotes do arquivo
        $this->add(24, 29, Util::formatCnab(9, ($this->qtyRegistrosLote + 4), 6)); // Qtd de lotes do arquivo
        $this->add(30, 46, Util::formatCnab(9, $this->valorTotalTitulos, 15, 2));
        $this->add(47, 115, Util::formatCnab(9, 0, 63)); // Qtd de lotes do arquivo
        $this->add(116, 240, ''); // Reservado (Uso Banco)

        return $this;
    }

}
