//{extends file="[default]backend/order/view/detail/window.js"}
//{block name="backend/order/view/detail/window" append}
//{namespace name=backend/order/main}

Ext.define('Shopware.apps.Order.view.detail.HeidelBackend', {
	override: 'Shopware.apps.Order.view.detail.Window',
	createTabPanel: function(){
		var me = this;
		var tabPanel = me.callParent(arguments);

//		console.log('record:');
//		console.log(me.record);

		var base = '';
		if(me.record.raw.shop.basePath != null){
			base = me.record.raw.shop.basePath;
		}
		var pluginpath = '/engine/Shopware/Plugins/Community/Frontend/HeidelGateway';

        if(me.record && me.record.raw.transactionId != ''){
			if((me.record.raw.payment.action == 'PaymentHgw')||(me.record.raw.payment.action == 'payment_heidelpay')){				
				var renderId = me.record.data.id;
				tabPanel.add({
					title: 'Heidelpay',
					items: [],
					listeners: {
						activate: function () {
							//me.loadTab(transID, payment.name, payment.description, additional id)
							me.loadTab(me.record.raw.transactionId, me.record.raw.payment.name, me.record.raw.payment.description, renderId);
						},
						deactivate: function() {
							Ext.getCmp('cont-heidel-'+renderId).destroy();
						},
					},
					html: '<link href="'+base+pluginpath+'/Views/backend/_resources/styles/font-awesome.min.css" type="text/css" rel="stylesheet"><link href="'+base+pluginpath+'/Views/backend/_resources/styles/heidel_backend.css" type="text/css" rel="stylesheet">',
					id: 'heidelbackend-'+renderId,
					layout: 'fit',
					border: 0,
					bodyBorder: 0,
					record: me.record
				});
			}
        }
		return tabPanel;
	},
	
	loadTab: function(transID, payName, payDesc, renderId) {
		var me = this;
		me.loadData(transID, payName, payDesc);

		Ext.create('Ext.container.Container', {
			xtype: 'container',
			id: 'cont-heidel-'+renderId,
			cls: 'cont-heidel',
			autoScroll: true,
			layout: 'fit',
            width: '100%',
            height: '100%',
            html: '<div class="loading" style="width: 90%; margin: auto; margin-top: 20px; text-align: center; font-size: 14px;">Loading..<br/><br/><span class="fa fa-spinner fa-spin fa-3x"></span></div>',
			renderTo: 'heidelbackend-'+renderId+'-body',
        });
	},

	loadData: function(transID, payName, payDesc) {
		var me = this;
		Ext.Ajax.request({
			url: '{url controller="BackendHgw" action="loadData"}',
			method: 'POST',
			params: {
				'transID':transID,
				'payName':payName,
				'payDesc':payDesc,
			},
			callback: function(options, success, response) {
				if(success){
					var resp = Ext.decode(response.responseText);
					me.updateDiv(resp);
				}else{
					console.log('server-side failure with status code ' + response.status);
				}
			},			
		});
	},

	updateDiv: function(resp) {
		var me = this;		
		var renderId = me.record.data.id;
		var parent = '#heidelbackend-'+renderId;
		var cont = Ext.getCmp('cont-heidel-'+renderId);

		if(resp.transCount != '0'){
			cont.update('<div class="loader"><span class="fa fa-spinner fa-spin fa-3x"></span></div><div class="cont-heidel-inner"><h1>'+resp.snippets.pay+': '+resp.methName+'</h1>'+resp.buttons.table+resp.action+resp.transTable+'<div class="note">'+resp.snippets.note+'</div></div><div class="heideltoolbar"><p class="text">'+resp.snippets.refresh+':</p><div id="reload"></div><div class="clearer"></div></div>');
		}else{
			cont.update('<div class="notrans"><span class="fa fa-warning fa-2x"></span>'+resp.snippets.notrans+'</div>');
			return;
		}

		var i = 0;
		var activeMeth = '';

		Ext.Object.each(resp.buttons.ref, function(key, value, myself){
            button = Ext.select('#buttontable .'+key);

			if(i == 0){ me.changeAction(button, key, value); activeMeth = key; }
			button.on('click', function(){ me.changeAction(this, key, value); activeMeth = key; });
			i++;
		});

		if(i > 0){
			var submit = Ext.select(parent+' #submit');
			var amount = Ext.select(parent+' #amount');

			submit.on('click', function(){
				if(typeof resp.buttons.ref[activeMeth] !== 'undefined'){
					if((amount.elements[0].value != '') && (amount.elements[0].value.replace(',','.') > 0)){
						Ext.select('#cont-heidel-'+renderId+' .loader').show();
						amount.removeCls('error');

						Ext.Ajax.request({
							url: '{url controller="BackendHgw" action="request"}',
							method: 'POST',
							params: {
								'meth':activeMeth,
								'trans': Ext.encode(resp.buttons.ref[activeMeth].trans[0]),
								'amount': amount.elements[0].value,
								'transID':resp.transID,
								'modul':me.record.raw.payment.name,
							},
							jsonData: resp.buttons.ref[activeMeth].trans[0],
							callback: function(options, success, response) {
								Ext.select('#cont-heidel-'+renderId+' .loader').hide();
								if(success){
									var requestResp = Ext.decode(response.responseText);
									if(requestResp.reload == 'true'){
										me.loadData(me.record.raw.transactionId, me.record.raw.payment.name, me.record.raw.payment.description);
									}
								}else{
									console.log('server-side failure with status code ' + response.status);
								}
							},			
						});
					}else{
						amount.addCls('error');
					}
				}
			});
			
			// remove everything more than two decimals
			amount.on('keyup', function(){
				var amountVal = amount.elements[0].value;
				
				if(amountVal.indexOf('.') != -1){
					var decimalPos = amountVal.indexOf('.');
				}else if(amountVal.indexOf(',') != -1){
					var decimalPos = amountVal.indexOf(',');
				}
				
				if(typeof decimalPos !== 'undefined'){
					this.value = amountVal.substring(0,decimalPos+3);
				}
			});
		}
		
		var reload = Ext.select(parent+' #reload');
		reload.on('click', function() {
			Ext.select('#cont-heidel-'+renderId+' .loader').show();
			Ext.Ajax.request({
				url: '{url controller="BackendHgw" action="getUpdate"}',
				method: 'POST',
				params: {
					'transID':resp.transID,
					'transCount':resp.transCount,
					'payName':me.record.raw.payment.name,
				},
				callback: function(options, success, response) {
					Ext.select('#cont-heidel-'+renderId+' .loader').hide();
					if(success){
						var reloadResp = Ext.decode(response.responseText);						
						if(reloadResp.update == 'true'){
							var transtable = Ext.select(parent+' #transtable');							
							transtable.update(reloadResp.transTable);
							var buttontable = Ext.select(parent+' #buttontable');
							buttontable.update(reloadResp.buttons.table);
							
							var j = 0;							
							Ext.Object.each(reloadResp.buttons.ref, function(key, value, myself){
								button = Ext.select('#buttontable .'+key);
								
								if(j == 0){ me.changeAction(button, key, value); activeMeth = key; }
								button.on('click', function(){ me.changeAction(this, key, value); activeMeth = key; });
								j++;
							});							
							resp.buttons = reloadResp.buttons;
						}					
					}else{
						console.log('server-side failure with status code ' + response.status);
					}
				},			
			});		
		});
	},

	changeAction: function(elem, key, value) {
		var me = this;
		var parent = '#heidelbackend-'+me.record.data.id;
		
		Ext.select(parent+' #buttontable .blue').removeCls('blue');
		Ext.get(elem).addCls('blue');

		var typename = Ext.select(parent+' #typename');
		var amount = Ext.select(parent+' #amount');
		typename.update(value.name);
		
		if(key == 'rf'){ amount.elements[0].value = value.trans[0].maxRf; }
		else if(key == 'rv'){ amount.elements[0].value = value.trans[0].maxRv; }
		else if(key == 'rv1'){ amount.elements[0].value = value.trans[0].maxRv1; }
		else if(key == 'rv2'){ amount.elements[0].value = value.trans[0].maxRv2; }
		else if(key == 'rv3'){ amount.elements[0].value = value.trans[0].maxRv3; }
		else if(key == 'cp'){ amount.elements[0].value = value.trans[0].maxCp; }
		else if(key == 'fi'){ amount.elements[0].value = value.trans[0].maxFi; }
		else{ amount.elements[0].value = '0.00'; }
	},
});
//{/block}