<section class="content-wrapper">

    <?php
    ini_set("auto_detect_line_endings", true);    // Treat EOL from all architectures
    $data['pageinfo'] = "application_labels";  $this->load->view('templates/content_header',$data);
    ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box">
                    <div class="box-body">
                        <div class="form-group">
                            <button class="btn btn-primary" id="print_selecteds"><i class="fas fa-print"></i> Imprimir Selecionados</button>
                        </div>
                        <table id="etiquetas" class="table table-striped table-hover responsive display table-condensed">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?=$this->lang->line('application_clients')?></th>
                                    <th><?=$this->lang->line('application_date')?></th>
                                    <th><?=$this->lang->line('application_value')?></th>
                                    <th><?=$this->lang->line('application_store')?></th>
                                    <th><?=$this->lang->line('application_tracking_code')?></th>
                                    <th><?=$this->lang->line('application_nfe_num')?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</section>

<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.5/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.5/css/responsive.dataTables.min.css"/>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css"/>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/datatables.net/js/processing.js"></script>

<script>
    var manageTableEtiquetasGeradas;
    var base_url = "<?php echo base_url(); ?>";

    $(function () {

        $("#mainLogisticsNav").addClass('active');
        $("#manageOrdersTagsTranspNav").addClass('active');

        manageTableEtiquetasGeradas = $('#etiquetas').DataTable( {
            "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
            "processing": true,
            "responsive": true,
            "sortable": true,
            "order": [[ 0, 'desc' ]],
            "createdRow": row => {
                $( row ).each(function (e,v){
                    $(v).find('td:eq(3)').attr('data-order',realToNumber($(v).find('td:eq(3)').text().replace('R$', '')));
                    $(v).find('td:eq(2)').attr('data-order',getTimeDateBr($(v).find('td:eq(2)').text()));
                })
            }
        });

        viewTagsTransmit();

    });

    const viewTagsTransmit = () => {
        const url       = "<?=base_url('orders/fetchEtiquetasTransp')?>";
        let rowsTable   = new Array();

        $.ajax({
            url,
            type: "GET",
            dataType: 'json',
            success: response => {
                $.each(response, function( index, value ) {
                    rowsTable.push(value);
                });

                manageTableEtiquetasGeradas.clear().draw().rows.add(rowsTable).columns.adjust().draw().processing(false);

            }, error: error => {
                console.log(error);
            }
        });
    }

    // converte valor de R$ -> Float
    const realToNumber = numero => {
        if(numero === undefined) return false;
        numero = numero.toString();
        numero = numero.replace(/\./g, "").replace(/,/g, ".");
        return parseFloat(numero);
    }

    // Formata data dd/mm/yyyy -> yyyy-mm-dd
    const getTimeDateBr = data => {
        if(data == null) return false;

        const length = data.length;

        if (length !== 10) return false;

        const splitDateTime = data.split(' ');
        const splitData = splitDateTime[0].split('/');

        const ano = splitData[2];
        const mes = splitData[1] - 1;
        const dia = splitData[0];

        const date = new Date(ano, mes, dia);
        return date.getTime()
    }

    $('#print_selecteds').click(function (){
        let orders = new Array();
        $('#etiquetas td input[type="checkbox"]:checked').each(function () {
            orders.push($(this).val());
        });

        if (orders.length === 0) {
            Toast.fire({
                icon: 'error',
                title: 'Nenhum pedido selecionado'
            });
            return false;
        }

        window.location.href = 'createTagTransp/' + orders.join('-');
    });

</script>