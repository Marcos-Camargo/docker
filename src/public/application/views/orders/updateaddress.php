<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_change_delivery_address";
    $this->load->view('templates/content_header', $data); ?>
    <!-- Main content -->
    <section class="content">

        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>

<?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo $this->session->flashdata('success'); ?>
                    </div>
<?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo $this->session->flashdata('error'); ?>
                    </div>
<?php endif; ?>


                <div class="box">
                    <form role="form" action="<?= base_url(); ?>orders/updateAddress" method="post">
                        <div class="box-body">
                            <?php
                            if (validation_errors()) {
                                foreach (explode("</p>", validation_errors()) as $erro) {
                                    $erro = trim($erro);
                                    if ($erro != "") {
                                        ?>
                                        <div class="alert alert-error alert-dismissible" role="alert">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <?php echo $erro . "</p>"; ?>
                                        </div>
                                    <?php
                                    }
                                }
                            }
                            ?>



                            <div class="row"></div>

                            <div class="col-md-12 col-xs-12 pull pull-left">
                                <h3><?= $this->lang->line('application_change_delivery_address') ?> </h3> 
                            </div>

                            <div class="form-group col-md-5">
                                <label for="customer_address"><?= $this->lang->line('application_address'); ?> </label> 
                                <div>
                                    <input type="text" required="required" class="form-control" id="customer_address" name="customer_address"  value="<?php echo $order_data['customer_address'] ?>" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="form-group col-md-1">
                                <label for="customer_address_num"><?= $this->lang->line('application_number'); ?></label>
                                <div>
                                    <input type="text" required="required" class="form-control" id="customer_address_num" name="customer_address_num"  value="<?php echo $order_data['customer_address_num'] ?>" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="customer_address_compl"><?= $this->lang->line('application_complement'); ?></label>
                                <div>
                                    <input type="text" class="form-control" id="customer_address_compl" name="customer_address_compl"  value="<?php echo $order_data['customer_address_compl'] ?>" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="customer_address_neigh"><?= $this->lang->line('application_neighb'); ?></label>
                                <div>
                                    <input type="text" class="form-control" required="required" id="customer_address_neigh" name="customer_address_neigh"  value="<?php echo $order_data['customer_address_neigh'] ?>" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="customer_address_city"><?= $this->lang->line('application_city'); ?></label>
                                <div>
                                    <input type="text" class="form-control" required="required" id="customer_address_city" name="customer_address_city"  value="<?php echo $order_data['customer_address_city'] ?>" autocomplete="off"/>
                                </div>
                            </div>
                            
                            <div class="form-group col-md-2">
                                <label for="customer_address_uf"><?=$this->lang->line('application_uf');?></label>
                                <select class="form-control" id="customer_address_uf" name="customer_address_uf" required="required">
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($ufs as $k => $v): ?>
                                        <option value="<?php echo trim($k); ?>" <?= ($order_data['customer_address_uf'] == trim($k) ? 'selected="selected"' : "" ); ?>><?php echo $v ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            
                            <div class="form-group col-md-2">
                                <label for="customer_address_zip"><?= $this->lang->line('application_zip_code'); ?></label>
                                <div>
                                    <input type="text" class="form-control" required="required" id="customer_address_zip" name="customer_address_zip" onblur="consultZip(this.value)"  value="<?php echo $order_data['customer_address_zip'] ?>" autocomplete="off"/>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="customer_reference"><?= $this->lang->line('application_reference'); ?></label>
                                <div>
                                    <input type="text" class="form-control" id="customer_reference" name="customer_reference"  value="<?php echo $order_data['customer_reference'] ?>" autocomplete="off"/>
                                </div>
                            </div>

                        </div>

                        <div class="box-footer">
                            <input type="hidden" name="id" value="<?= $order_data['id'] ?>" autocomplete="off">
                            
                            <button type="submit" class="btn btn-primary"><?= $this->lang->line('application_save'); ?></button>
                            <a href="<?php echo base_url('orders/') ?>" class="btn btn-warning"><?= $this->lang->line('application_back'); ?></a>
                        </div>

                </div>

                </form>
            </div>
            <!-- /.box -->
        </div>

    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<style>
    ol.timeline-occurrence {
        margin: 0;
        list-style: none;
        padding: 0;
        --hue: 1;
        --unit: 1rem;
    }
    .timeline-occurrence p {
        line-height: 1.3;
    }
    .timeline-occurrence .event-date {
        margin: 0 0 0.25rem;
        font-weight: bold;
    }
    .timeline-occurrence .event-description {
        margin: 0;
    }
    .timeline-occurrence li {
        --height: 7rem;
        position: relative;
        display: block;
        background-color: #aaa;
        border-color: #aaa;
        color: #000;
        padding: 1rem;
        margin: 2rem 0;
    }
    .timeline-occurrence li::before {
        content: "";
        background-color: inherit;
        position: absolute;
        display: block;
        width: var(--unit);
        height: var(--unit);
        top: 100%;
        left: calc(50% - (var(--unit)/2));
    }
    .timeline-occurrence li::after {
        content: "";
        position: absolute;
        display: block;
        top: calc(100% + var(--unit));
        left: calc(50% - (var(--unit)));
        border: var(--unit) solid transparent;
        border-top-color: inherit;
    }
    .timeline-occurrence li:last-child::before,
    .timeline-occurrence li:last-child::after {
        content: none;
    }
    .timeline-occurrence li:nth-child(20n+1){
        --hue: 1;
    }
    .timeline-occurrence li:nth-child(20n+2){
        --hue: 2;
    }
    .timeline-occurrence li:nth-child(20n+3){
        --hue: 3;
    }
    .timeline-occurrence li:nth-child(20n+4){
        --hue: 4;
    }
    .timeline-occurrence li:nth-child(20n+5){
        --hue: 5;
    }
    .timeline-occurrence li:nth-child(20n+6){
        --hue: 6;
    }
    .timeline-occurrence li:nth-child(20n+7){
        --hue: 7;
    }
    .timeline-occurrence li:nth-child(20n+8){
        --hue: 8;
    }
    .timeline-occurrence li:nth-child(20n+9){
        --hue: 9;
    }
    .timeline-occurrence li:nth-child(20n+10){
        --hue: 10;
    }
    @media (min-width: 550px) and (max-width: 899px){
        .timeline-occurrence li {
            margin: 1rem;
            width: calc(50% - 2rem);
            float: left;
            min-height: var(--height);
        }
        .timeline-occurrence li:nth-child(4n+3),
        .timeline-occurrence li:nth-child(4n+4) {
            float: right;
        }
        .timeline-occurrence li:nth-child(4n+1)::before {
            top: calc(var(--height)/2 + var(--unit)/2);
            left: 100%;
        }
        .timeline-occurrence li:nth-child(4n+1)::after {
            top: calc(var(--height)/2);
            left: calc(100% + (var(--unit)));
            border: var(--unit) solid transparent;
            border-left-color: inherit;
        }
        .timeline-occurrence li:nth-child(4n+3)::before {
            top: calc(var(--height)/2 + var(--unit)/2);
            left: -1rem;
        }
        .timeline-occurrence li:nth-child(4n+3)::after {
            top: calc(var(--height)/2);
            left: -3rem;
            border: var(--unit) solid transparent;
            border-right-color: inherit;
        }
    }
    @media (min-width: 900px) and (max-width: 1199px){
        .timeline-occurrence li {
            margin: 1rem;
            width: calc(33.33% - 2rem);
            float: left;
            min-height: 7rem;
        }
        .timeline-occurrence li:nth-child(6n+4),
        .timeline-occurrence li:nth-child(6n+5),
        .timeline-occurrence li:nth-child(6n+6) {
            float: right;
        }
        .timeline-occurrence li:nth-child(6n+1)::before,
        .timeline-occurrence li:nth-child(6n+2)::before {
            top: calc(var(--height)/2 + var(--unit)/2);
            left: 100%;
        }
        .timeline-occurrence li:nth-child(6n+1)::after,
        .timeline-occurrence li:nth-child(6n+2)::after {
            top: 3.5rem;
            left: calc(100% + (var(--unit)));
            border: var(--unit) solid transparent;
            border-left-color: inherit;
        }
        .timeline-occurrence li:nth-child(6n+4)::before,
        .timeline-occurrence li:nth-child(6n+5)::before{
            top: calc(var(--height)/2 + var(--unit)/2);
            left: -1rem;
        }
        .timeline-occurrence li:nth-child(6n+4)::after,
        .timeline-occurrence li:nth-child(6n+5)::after{
            top: calc(var(--height)/2);
            left: -3rem;
            border: var(--unit) solid transparent;
            border-right-color: inherit;
        }
    }
    @media (min-width: 1200px){
        ol.timeline-occurrence {
            max-width: 1600px;
            margin: 0 auto;
        }
        .timeline-occurrence li {
            margin: 1rem;
            width: calc(26.9% - 4rem);
            float: left;
            min-height: 7rem;
        }
        .timeline-occurrence li:nth-child(8n+5),
        .timeline-occurrence li:nth-child(8n+6),
        .timeline-occurrence li:nth-child(8n+7),
        .timeline-occurrence li:nth-child(8n+8){
            float: right;
        }
        .timeline-occurrence li:nth-child(8n+1)::before,
        .timeline-occurrence li:nth-child(8n+2)::before,
        .timeline-occurrence li:nth-child(8n+3)::before{
            top: calc(var(--height)/2 + var(--unit)/2);
            left: 100%;
        }
        .timeline-occurrence li:nth-child(8n+1)::after,
        .timeline-occurrence li:nth-child(8n+2)::after,
        .timeline-occurrence li:nth-child(8n+3)::after{
            top: calc(var(--height)/2);
            left: calc(100% + (var(--unit)));
            border: var(--unit) solid transparent;
            border-left-color: inherit;
        }
        .timeline-occurrence li:nth-child(8n+5)::before,
        .timeline-occurrence li:nth-child(8n+6)::before,
        .timeline-occurrence li:nth-child(8n+7)::before {
            top: calc(var(--height)/2 + var(--unit)/2);
            left: -1rem;
        }
        .timeline-occurrence li:nth-child(8n+5)::after,
        .timeline-occurrence li:nth-child(8n+6)::after,
        .timeline-occurrence li:nth-child(8n+7)::after {
            top: calc(var(--height)/2);
            left: -3rem;
            border: var(--unit) solid transparent;
            border-right-color: inherit;
        }
    }
</style>

<script>
   function consultZip(id) {
        $.ajax({
            url: 'https://viacep.com.br/ws/'+id+'/json/',
            type: 'get',
            dataType: 'json',
            success: function(response) {
                $('#customer_address')[0].value = response.logradouro
                $('#customer_address_neigh')[0].value = response.bairro
                $('#customer_address_city')[0].value = response.localidade
                $('#customer_address_uf')[0].value = response.uf
                //$('#business_nation')[0].value = 'BR'
                return
            }
        });
    }    
</script>