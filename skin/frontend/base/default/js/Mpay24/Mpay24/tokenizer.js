window.onload = function() {
  window.addEventListener("message", checkValid, false);
}

function checkValid(d) {
  var data = JSON.parse(d.data);
  
  document.getElementById('tokenizer_error').style.display="none";
  //document.getElementById('payment-buttons-container').getElementsByTagName('button')[0].disabled = true;
  
  var cc = [ ];
  if(document.getElementById('AMEX').value == 1)
    cc.push("amex");
  if(document.getElementById('DINERS').value == 1)
    cc.push("diners");
  if(document.getElementById('JCB').value == 1)
    cc.push("jcb");
  if(document.getElementById('MASTERCARD').value == 1)
    cc.push("mastercard");
  if(document.getElementById('VISA').value == 1)
    cc.push("visa");
  
  if (typeof data.brandChange !== 'undefined' && data.brandChange != "none" && cc.indexOf(data.brandChange) == -1) {
    document.getElementById('tokenizer_brand').innerHTML="&nbsp;" + data.brandChange + "&nbsp;";
    document.getElementById('tokenizer_error').style.display="flex";
    //document.getElementById('payment-buttons-container').getElementsByTagName('button')[0].disabled = false;
  }
}
