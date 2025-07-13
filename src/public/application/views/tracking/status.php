<?php $this->load->view('templates/header'); ?>
<style>
/* Timeline */
.timeline::before {
  background-color: #fff;
}
.timeline-badge {
  color: #fff;
  width: 54px;
  height: 54px;
  line-height: 52px;
  font-size: 22px;
  text-align: center;
  position: absolute;
  top: 18px;
  left: 50%;
  margin-left: -25px;
  border-top-right-radius: 50%;
  border-top-left-radius: 50%;
  border-bottom-right-radius: 50%;
  border-bottom-left-radius: 50%;
}

.timeline-badge .glyphicon {
  left: -1px;
  top: 17%;
}
.timeline-badge.primary {
  background-color: #1f9eba;
  height: 67px;
  width: 67px;
}

.timeline-badge.info {
  background-color: #777;
  height: 67px;
  width: 67px
}

.timeline-horizontal {
  list-style: none;
  position: relative;
  padding: 20px 0px 20px 0px;
  display: inline-block;
}

.timeline-item {
  display: table-cell;
  height: 100px;
  width: 20%;
  min-width: 150px;
  float: none !important;
  padding-left: 0px;
  /* padding-right: 20px; */
  margin: 0 auto;
  vertical-align: bottom;
}

.timeline-horizontal .timeline-item .timeline-badge {
  top: auto;
  bottom: 0px;
  left: 51px;
}
.textStatus {
    margin-top: 10px;
    width: 120px;
    height: 0px;
    text-align: center;
}
.textStatus p{
  margin-top: 10px;
}
.estrutura {
  display: flex;
  /*width: 91vw;*/
  overflow-y: auto;
  justify-content: center;
}
.page {
  display: flex;
  flex-wrap: wrap;
  width: 100%;
  margin-top: 37px;
  margin-bottom: 7px;
}
.size-table {
  width: 50%;
}
@media only screen and (max-width: 600px) {
  .timeline .timeline-item .timeline-badge i,
  .timeline .timeline-item .timeline-badge .fa,
  .timeline .timeline-item .timeline-badge .glyphicon {
    left: 0px;
    top: 10%;
  }
  .timeline-horizontal .timeline-item {
    display: table-cell;
    height: 90px;
    width: 20%;
    min-width: 75px;
    float: none !important;
    padding-left: 0px;
    padding-right: 20px;
    /* margin: 0 auto; */
    vertical-align: bottom;
  }
  .timeline-item .timeline-badge.primary {
    background-color: #1f9eba;
    height: 55px;
    width: 55px;
  }
  .timeline-item .timeline-badge.info {
    background-color: #777;
    height:55px;
    width: 55px
  }
  .textStatus {
      width: 55px;
      height: 0px;
      text-align: center;
  }

  .timeline-horizontal .timeline-item .timeline-badge {
    top: auto;
    bottom: 0px;
    left: 29px;
  }
  .size-table {
    width: 100%;
  }
}

</style>
<section class="content-header">
    <h1>
        <?='Logística'?><small><?='Status de tracking'?></small>  
    </h1>
</section>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header page">
              <div class="col-md-6 text-left">Conecta lá</div>
              <div class="col-md-6 text-right">Número de Pedido: <span style="color: black; font-weight: bold;"><?=$id;?></span></div>
            </div>
            <div class="estrutura">
              <ul class="timeline timeline-horizontal">
                  <li class="timeline-item">
                      <div class="textStatus">
                        <div class="timeline-badge  <?=$step[0] == '1' ? 'primary' : 'info' ?>"><i class="glyphicon glyphicon-ok"></i></div>
                          <p>Criado</p>
                      </div>
                  </li>
                  <li class="timeline-item">
                      <div class="textStatus">
                          <div class="timeline-badge <?=$step[1] == '1' ? 'primary' : 'info' ?>"><i class="glyphicon glyphicon glyphicon-send"></i></div>
                          <p>Aguardando envio</p>
                      </div>
                  </li>
                  <li class="timeline-item">
                      <div class="textStatus">
                          <div class="timeline-badge  <?=$step[2] == '1' ? 'primary' : 'info' ?>"><i class="glyphicon glyphicon-hourglass"></i></div>
                          <p>Em Transito</p>
                      </div>
                  </li>
                  <li class="timeline-item">
                      <div class="textStatus">
                          <div class="timeline-badge  <?=$step[3] == '1' ? 'primary' : 'info' ?>"><i class="glyphicon glyphicon-envelope"></i></div>
                          <p>Saiu para Entrega</p>
                      </div>    
                  </li>
                  <li class="timeline-item">
                      <div class="textStatus">
                          <div class="timeline-badge <?=$step[4] == '1' ? 'primary' : 'info' ?>"><i class="glyphicon glyphicon-home"></i></div>
                          <p>Entregue</p>
                      </div>
                  </li>
              </ul>
          </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <td>Produto</td>
                  <td>Quantidade</td>
                  <td>Preço</td>
                </tr>
              </thead>
              <tbody>
                <?php foreach($item as $i) { ?>
                  <tr>
                    <td><?=$i['name']?></td>
                    <td><?=$i['qty']?></td>
                    <td>R$ <?=number_format($i['rate'],2)?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
          <div class="page-header page">
              <div style="width: 70%;">Transportadora: <span style="color: black; font-weight: bold;"> <?= is_null($info['ship_company_preview']) ? '': $info['ship_company_preview'].' - '. $info['ship_service_preview']?></span></div>
          </div>
        </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <table class="table table-striped table-bordered size-table">
          <thead>
            <tr>
              <td>Data/ Hora do Evento</td>
              <td>Tipo de Evento</td>
            </tr>
          </thead>
          <tbody>
            <?php foreach($historico as $i) { ?>
              <tr>
                <td><?=$i['data_ocorrencia']?></td>
                <td><?=$i['nome']?></td>

              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
</div>