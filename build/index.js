(()=>{"use strict";const e=window.wp.element,t=window.wp.i18n,n=window.wc.wcBlocksRegistry,o=window.wp.htmlEntities,a=(0,window.wc.wcSettings.getSetting)("quotes-gateway_data",{}),i=(0,t.__)("Ask for Quote","quote-wc"),c=(0,o.decodeEntities)(a.title)||i,l=()=>(0,o.decodeEntities)(a.description||""),s={name:"quotes-gateway",label:(0,e.createElement)((t=>{const{PaymentMethodLabel:n}=t.components;return(0,e.createElement)(n,{text:c})}),null),content:(0,e.createElement)(l,null),edit:(0,e.createElement)(l,null),placeOrderButtonLabel:(0,t.__)((0,o.decodeEntities)(a.place_order_label),"quote-wc"),canMakePayment:()=>!0,ariaLabel:c,supports:{features:a.supports}};(0,n.registerPaymentMethod)(s)})();