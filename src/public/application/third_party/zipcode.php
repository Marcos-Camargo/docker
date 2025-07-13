		<?php 
		$ufs = array(		  
		    'AC' => 'Acre',
		    'AL' => 'Alagoas',
		    'AP' => 'Amapá',
		    'AM' => 'Amazonas',
		    'BA' => 'Bahia',
		    'CE' => 'Ceará',
		    'DF' => 'Distrito Federal',
		    'ES' => 'Espírito Santo',
		    'GO' => 'Goiás',
		    'MA' => 'Maranhão',
		    'MT' => 'Mato Grosso',
		    'MS' => 'Mato Grosso do Sul',
		    'MG' => 'Minas Gerais',
		    'PA' => 'Pará',
		    'PB' => 'Paraíba',
		    'PR' => 'Paraná',
		    'PE' => 'Pernambuco',
		    'PI' => 'Piauí',
		    'RJ' => 'Rio de Janeiro',
		    'RN' => 'Rio Grande do Norte',
		    'RS' => 'Rio Grande do Sul',
		    'RO' => 'Rondônia',
		    'RR' => 'Roraima',
		    'SC' => 'Santa Catarina',
		    'SP' => 'São Paulo',
		    'SE' => 'Sergipe',
		    'TO' => 'Tocantins');
		$paises = Array(
			'BR' => 'Brasil',
			'SP' => 'España');			    
			    
		?>
<!-- SWDelta - Address AutoComplete based on ZIPCODE -->
<script type='text/javascript'>

			var Rastro = function(){
			   this.region = {
			    'AC' : 'Acre',
			    'AL' : 'Alagoas',
			    'AP' : 'Amapá',
			    'AM' : 'Amazonas',
			    'BA' : 'Bahia',
			    'CE' : 'Ceará',
			    'DF' : 'Distrito Federal',
			    'ES' : 'Espírito Santo',
			    'GO' : 'Goiás',
			    'MA' : 'Maranhão',
			    'MT' : 'Mato Grosso',
			    'MS' : 'Mato Grosso do Sul',
			    'MG' : 'Minas Gerais',
			    'PA' : 'Pará',
			    'PB' : 'Paraíba',
			    'PR' : 'Paraná',
			    'PE' : 'Pernambuco',
			    'PI' : 'Piauí',
			    'RJ' : 'Rio de Janeiro',
			    'RN' : 'Rio Grande do Norte',
			    'RS' : 'Rio Grande do Sul',
			    'RO' : 'Rondônia',
			    'RR' : 'Roraima',
			    'SC' : 'Santa Catarina',
			    'SP' : 'São Paulo',
			    'SE' : 'Sergipe',
			    'TO' : 'Tocantins'
			  };
			
			  this.fieldmap = [
			    {'logradouro'  : 'input[name*="address"]'},
			    {'complemento' : 'input[name*="add_compl"]'},
			    {'bairro'      : 'input[name*="addr_neigh"]'},
			    {'localidade'  : 'input[name*="addr_city"]'},
			    {'uf'          : 'input[name*="addr_uf"]'},
			    {'uf'          : 'select[name*="addr_uf"]'}
			  ];
			  
			  //this.url = 'http://cep.republicavirtual.com.br/web_cep.php?cep=%postcode&formato=json';
			  //this.url = 'https://apps.widenet.com.br/busca-cep/api/cep.json?code=%postcode';
			  this.url = 'https://viacep.com.br/ws/%postcode/json/';
			};
			
			Rastro.prototype = {
			  getSelectors : function() {
			    return this.fieldmap.map(function(elem){
			      for (var i in elem) return elem[i]
			    });
			  },
			  /**
			   * Method to simplify the cleaning fields process
			   */
			  clear : function(elem) {
			    elem.form.select(this.getSelectors()).each(function(e){
			      e.setValue('');
			    });
			    return this;
			  },
			
			
			  /**
			   * Loads the result to the respective fields
			   */
			  autofill : function(elem, result) {
			    var _this = this;
				$("select[name*='country']").val('BR').attr('selected', true);
				$("select[name*='country']").trigger('change');	
			  	$.each( this.fieldmap, function( item, object ) {
				      for (var key in object) { 
					      field = object[key];
						  if ($(object[key]).length > 0) {
							  if (field.search('addr_uf')>=0) {
							  	$.each($(object[key]).prop("options"), function(i, opt) {
								  	if (opt.textContent==_this.region[result[key]]) {
								  		$(  object[key] ).val(opt.value).attr('selected', true);	
								  		$(  object[key] ).trigger('change');					  	
								  	}
				            	});
							  } else {
							      $( object[key] ).val(result[key]);
							      $( object[key] ).trigger('change');
							  }    
						  }    
				      }
			    });
			    return this;
			  },
			
			  /**
			   * Sends the request and manages the result
			   */
			  init : function(elem) {
			    var _this = this;
			    let valueZipcode = elem.value.replace(/\D/g, "");
				if (valueZipcode.length !== 8) return this;
			    $.get(this.url.replace(/%postcode/g, valueZipcode), {cep:valueZipcode}, function(response){
			      if (response != false) {
			        _this.autofill(elem, response);
			      } else {
			        _this.clear(elem);
			      }
			    }).fail(function(){
			      _this.clear(elem);
			    });
			    return this;
			  }
			};		    
			let timerId = setInterval(function() {
				if ($("input[name*='zipcode']").length > 0) {
					clearInterval(timerId);
					$( "input[name='zipcode']" ).keyup(function() {
						(new Rastro()).init(this);
					});
				}	  
			}, 2000);
</script>    	
