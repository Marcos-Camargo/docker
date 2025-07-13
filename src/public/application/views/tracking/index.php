<?php $this->load->view('templates/header'); ?>

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>
<style>

.pointer {
    cursor: pointer;
}

.legend2 {
    display: block;
    padding: 0;    
    font-size: 21px;
    line-height: inherit;
    color: #333;
    border: 0;
    padding: 10px;
    width: 11%;
}

fieldset {
    min-width: 0;
    padding: 9px;
    margin: 1px;
    border: 1px solid #0066CC;
    padding-bottom: 47px;;
}
.divTable {
    display: flex;
    justify-content: center;
    flex-direction: column;
}

.formCpfCnpj {
    width:35%;
    display: inline-block;
}
@media only screen and (max-width: 600px) {

.formCpfCnpj {
    width:50%;
    display: inline-block;
}
}
</style>
<section class="content-header">

    <h1>
        <?='Logística'?><small><?='Página de Tracking'?></small>  
    </h1>
    <fieldset style="margin: 40px;">
        <legend class="legend2">Rastreio de Pedidos</legend>
        <div class="row text-center">

            <div class="row text-center">
                <div class="form-group">
                    <label style="margin-top: 17px; font-size:20px;">Informe o CPF/CNPJ</label>
                </div>
                <input type="text" id="cpf_cnpj" class="form-control formCpfCnpj"></div>
            </div>
            <div class="row text-center" style="margin-top: 17px;">
                <button class="btn btn-primary" id="consultaPedidos">Consultar Pedidos</button>
            </div>
        </div>
    </fieldset>
</section>
<section id="resultado-pedido" class="divTable" style="display: none;">
    <div class="row" style="text-align: center;">
        <div class="col-md-12">
            <button class="btn btn-primary" id="all">Exibir Todos</button>
            <button class="btn btn-primary" id="three" disabled>Exibir 3 meses</button>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12" style="display: flex; justify-content: center;">
            <table id="table-pedido" class="table table-striped table-bordered" style="width:90%">
                <thead>
                    <tr>
                        <th>
                            Número do Pedido
                        </th>
                        <th>
                            Usuário
                        </th>
                        <th>
                            Endereço
                        </th>
                        <th>
                            Logistica
                        </th>
                    </tr>
                </thead>
                <tbody>
                    
                </tbody>
            </table>
        </div>
    </div>
</section>
<script type="text/javascript">
$("input[id*='cpf_cnpj']").inputmask({
  mask: ['999.999.999-99', '99.999.999/9999-99'],
  keepStatic: true
});

$("#consultaPedidos").click( function(){
    
    var cpf_cnpj =  $("input[id*='cpf_cnpj']").val();

    console.log( cpf_cnpj, cpf_cnpj.length );

    if(cpf_cnpj.length == 14 ) {
        var cpf = isCpf(cpf_cnpj);
        if(cpf === false ) {
            alert("Informe um CPF/CNPJ válido");
            return false;
        }
    }
    
    if(cpf_cnpj.length == 18 ) {
        var cnpj = isCNPJValid(cpf_cnpj);
        if(cnpj === false ) {
            alert("Informe um CPF/CNPJ válido");
            return false;
        }
    }


    let table = '';
    $('#table-pedido tbody').html(table);
    $('#resultado-pedido').hide();
    
    if( $("#cpf_cnpj").val() == ""){
        alert("Informe um CPF/CNPJ");
        return false;
    }

    $.ajax({
      
      url: '<?php echo base_url('tracking/searchorderbycpfcnpj')?>',
      type: 'get',
      data: {
        'cnpj_cpf': $("#cpf_cnpj").val(),
        'type': 'three'
      }, 
      dataType: 'json',
      success:function(response) {
        if (response.length == 0) {
            alert('CPF/CNPJ não possui pedido!')
            return false;
        }
        $.each(response, function(k,v) {
            a = btoa(v.id+'-'+ $("#cpf_cnpj").val()).replace(/[^0-9A-Za-z]/g, '');
            numer= v.numero_marketplace == null ? '': v.numero_marketplace; 
            table += '<tr onclick="openStatus(\''+a+'\')"><td><span class="pointer">'+numer+'</span></td><td>'+v.customer_name+'</td><td>'+ v.customer_address+'</td><td>'+v.origin+'</td></tr>' 
        });
        $('#resultado-pedido').show();
        $('#table-pedido tbody').html(table);
      }
    });
});

$("#three").click( function(){
    $('#all').removeAttr('disabled')
    $('#three').attr('disabled','disabled')

    let table = '';
    $('#table-pedido tbody').html(table);
    $('#resultado-pedido').hide();
    
    if( $("#cpf_cnpj").val() == ""){
        alert("Informe um CPF/CNPJ");
        return false;
    }
    
    $.ajax({
        url: '<?php echo base_url('tracking/searchorderbycpfcnpj')?>',
      type: 'get',
      data: {
        'cnpj_cpf': $("#cpf_cnpj").val(),
        'type': 'three'
      }, 
      dataType: 'json',
      success:function(response) {
        if (response.length == 0) {
            alert('CPF/CNPJ não possui pedido!')
            return false;
        }
        $.each(response, function(k,v) {
            a = btoa(v.id+'-'+ $("#cpf_cnpj").val()).replace(/[^0-9A-Za-z]/g, '');
            numer= v.numero_marketplace == null ? '': v.numero_marketplace; 
          table += '<tr onclick="openStatus(\''+a+'\')"><td>'+numer+'</td><td>'+v.customer_name+'</td><td>'+ v.customer_address+'</td><td>'+v.origin+'</td></tr>' 
        });
        $('#resultado-pedido').show();
        $('#table-pedido tbody').html(table);
      }
    });
});

$("#all").click( function(){
    $('#three').removeAttr('disabled')
    $('#all').attr('disabled','disabled')

    let table = '';
    $('#table-pedido tbody').html(table);
    $('#resultado-pedido').hide();
    
    if( $("#cpf_cnpj").val() == ""){
        alert("Informe um CPF/CNPJ");
        return false;
    }
    
    $.ajax({
      url: '<?php echo base_url('tracking/searchorderbycpfcnpj')?>',
      type: 'get',
      data: {
        'cnpj_cpf': $("#cpf_cnpj").val(),
        'type': 'all'
      }, 
      dataType: 'json',
      success:function(response) {
        if (response.length == 0) {
            alert('CPF/CNPJ não possui pedido!')
            return false;
        }
        $.each(response, function(k,v) {
            a = btoa(v.id+'-'+ $("#cpf_cnpj").val()).replace(/[^0-9A-Za-z]/g, '');
            numer= v.numero_marketplace == null ? '': v.numero_marketplace; 
          table += '<tr onclick="openStatus(\''+a+'\')"><td>'+numer+'</td><td>'+v.customer_name+'</td><td>'+ v.customer_address+'</td><td>'+v.origin+'</td></tr>' 
        });
        $('#resultado-pedido').show();
        $('#table-pedido tbody').html(table);
      }
    });
});


function isCpf(cpf) {
    exp = /\.|-/g;
    cpf = cpf.toString().replace(exp, "");
    var digitoDigitado = eval(cpf.charAt(9) + cpf.charAt(10));
    var soma1 = 0,
            soma2 = 0;
    var vlr = 11;
    for (i = 0; i < 9; i++) {
        soma1 += eval(cpf.charAt(i) * (vlr - 1));
        soma2 += eval(cpf.charAt(i) * vlr);
        vlr--;
    }
    soma1 = (((soma1 * 10) % 11) === 10 ? 0 : ((soma1 * 10) % 11));
    soma2 = (((soma2 + (2 * soma1)) * 10) % 11);
    if (cpf === "11111111111" || cpf === "22222222222" || cpf === "33333333333" || cpf === "44444444444" || cpf === "55555555555" || cpf === "66666666666" || cpf === "77777777777" || cpf === "88888888888" || cpf === "99999999999" || cpf === "00000000000") {
        var digitoGerado = null;
    } else {
        var digitoGerado = (soma1 * 10) + soma2;
    }
    if (digitoGerado !== digitoDigitado) {
        return false;
    }
    return true;
}

function isCNPJValid(cnpj) {
    cnpj = cnpj.replace(/[^\d]+/g, '');
    if (cnpj == '') return false;
    if (cnpj.length != 14)
        return false;
    // Elimina CNPJs invalidos conhecidos
    if (cnpj == "00000000000000" ||
        cnpj == "11111111111111" ||
        cnpj == "22222222222222" ||
        cnpj == "33333333333333" ||
        cnpj == "44444444444444" ||
        cnpj == "55555555555555" ||
        cnpj == "66666666666666" ||
        cnpj == "77777777777777" ||
        cnpj == "88888888888888" ||
        cnpj == "99999999999999")
        return false;

    // Valida DVs
    tamanho = cnpj.length - 2
    numeros = cnpj.substring(0, tamanho);
    digitos = cnpj.substring(tamanho);
    soma = 0;
    pos = tamanho - 7;
    for (i = tamanho; i >= 1; i--) {
        soma += numeros.charAt(tamanho - i) * pos--;
        if (pos < 2)
            pos = 9;
    }
    resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    if (resultado != digitos.charAt(0))
        return false;

    tamanho = tamanho + 1;
    numeros = cnpj.substring(0, tamanho);
    soma = 0;
    pos = tamanho - 7;
    for (i = tamanho; i >= 1; i--) {
        soma += numeros.charAt(tamanho - i) * pos--;
        if (pos < 2)
            pos = 9;
    }
    resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    if (resultado != digitos.charAt(1))
        return false;

    return true;
}

function openStatus(id) {
    window.location.href = '<?php echo base_url('tracking/status/')?>'+id;    
}
</script>