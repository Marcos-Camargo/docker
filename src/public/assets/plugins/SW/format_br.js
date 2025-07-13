//--->Função para a formatação dos campos...<---
function Mascara(tipo, campo, teclaPress) {
	if (window.event)
	{
		var tecla = teclaPress.keyCode;
	} else {
		tecla = teclaPress.which;
	}
 
	var s = new String(campo.value);
	
	// Remove todos os caracteres à seguir: ( ) / - . e espaço, para tratar a string denovo.
	s = s.replace(/(\.|\(|\)|\/|\-| |\:)+/g,'');
 
	tam = s.length + 1;
 
	if ( tecla != 9 && tecla != 8 ) {
		switch (tipo)
		{
		case 'CPF' :
			if (tam > 3 && tam < 7)
				campo.value = s.substr(0,3) + '.' + s.substr(3, tam);
			if (tam >= 7 && tam < 10)
				campo.value = s.substr(0,3) + '.' + s.substr(3,3) + '.' + s.substr(6,tam-6);
			if (tam >= 10 && tam < 12)
				campo.value = s.substr(0,3) + '.' + s.substr(3,3) + '.' + s.substr(6,3) + '-' + s.substr(9,tam-9);
		break;
 
		case 'CNPJ' :
 
			if (tam > 2 && tam < 6)
				campo.value = s.substr(0,2) + '.' + s.substr(2, tam);
			if (tam >= 6 && tam < 9)
				campo.value = s.substr(0,2) + '.' + s.substr(2,3) + '.' + s.substr(5,tam-5);
			if (tam >= 9 && tam < 13)
				campo.value = s.substr(0,2) + '.' + s.substr(2,3) + '.' + s.substr(5,3) + '/' + s.substr(8,tam-8);
			if (tam >= 13 && tam < 15)
				campo.value = s.substr(0,2) + '.' + s.substr(2,3) + '.' + s.substr(5,3) + '/' + s.substr(8,4)+ '-' + s.substr(12,tam-12);
		break;
 
		case 'TEL' :
			if (tam > 2 && tam < 4)
				campo.value = '(' + s.substr(0,2) + ') ' + s.substr(2,tam);
			if (tam >= 7 && tam < 11)
				campo.value = '(' + s.substr(0,2) + ') ' + s.substr(2,4) + '-' + s.substr(6,tam-6);
			if (tam == 12)
				campo.value = '(' + s.substr(0,2) + ') ' + s.substr(2,5) + '-' + s.substr(7,tam-7);
		break;
 
		case 'DATA' :
			if (tam > 2 && tam < 4)
				campo.value = s.substr(0,2) + '/' + s.substr(2, tam);
			if (tam > 4 && tam < 11)
				campo.value = s.substr(0,2) + '/' + s.substr(2,2) + '/' + s.substr(4,tam-4);
		break;
		//12345678901234
		//DDMMYYYyHHMMSS
		//0123456789012345678
		//DD/MM/YYYY HH:MM:ss
		case 'DATAHORA' :
			if (tam > 2 && tam < 4)
				campo.value = s.substr(0,2) + '/' + s.substr(2, tam);
			if (tam > 4 && tam < 8)
				campo.value = s.substr(0,2) + '/' + s.substr(2,2) + '/' + s.substr(4,tam-4);
			if (tam > 8 && tam < 10)
				campo.value = s.substr(0,2) + '/' + s.substr(2,2) + '/' + s.substr(4,4) + ' ' + s.substr(8, tam-8);
			if (tam > 10 && tam < 12)
				campo.value = s.substr(0,2) + '/' + s.substr(2,2) + '/' + s.substr(4,4) + ' ' + s.substr(8, 2) + ':' + s.substr(10, tam-10);
			if (tam > 12 && tam < 14)
				campo.value = s.substr(0,2) + '/' + s.substr(2,2) + '/' + s.substr(4,4) + ' ' + s.substr(8, 2) + ':' + s.substr(10, 2)+ ':' + s.substr(12,tam-12);
		break;
		
		case 'CEP' :
			if (tam > 5 && tam < 7)
				campo.value = s.substr(0,5) + '-' + s.substr(5, tam);
		break;

		case 'MOEDA' :
			if (tam > 3 && tam < 12)
				campo.value = s.substr(0,tam-3) + '.' + s.substr(tam-3, tam);
		break;
		case 'DEC2' :
			if (tam > 3 && tam < 12)
				campo.value = s.substr(0,tam-3) + '.' + s.substr(tam-3, tam);
		break;
		
		case 'NCM' :
			if (tam > 4 && tam < 6)
				campo.value = s.substr(0,4) + '.' + s.substr(4, tam);
			if (tam > 6 && tam < 11)
				campo.value = s.substr(0,4) + '.' + s.substr(4,2) + '.' + s.substr(6,tam-2);
		break;
		}
	}
}

//--->Função para verificar se o valor digitado é número...<---
function digitos(event){
	if (window.event) {
		// IE
		key = event.keyCode;
	} else if ( event.which ) {
		// netscape
		key = event.which;
	}
	if ( key != 8 || key != 13 || key < 48 || key > 57 )
		return ( ( ( key > 47 ) && ( key < 58 ) ) || ( key == 8 ) || ( key == 13 ) );
	return true;
}

function checkSpecialSku(e,tis)
{
	if(e.keyCode === 111) $(this).val($(tis).val().replace(/\//g,'-'));
	if($(tis).val().includes("/")) $(tis).val($(tis).val().replace(/\//g,'-'));
	$(tis).val($(tis).val().replace(/[^.a-zA-Z0-9@_-]/g,''));
}
