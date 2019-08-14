function setOnClick(text) {
  var radios = document.getElementsByName( 'payment[method]' );
  var onclick = "";
  for( i = 0; i < radios.length; i++ ) {
    if(radios[i].id == 'p_method_mpay24' || radios[i].id.substring(0, 15) != 'p_method_mpay24') {
      onclick = radios[i].onclick + " ";
      radios[i].setAttribute('onClick', onclick + "forceSelection('" + text + "');");
    }
  }
}

function forceSelection(text) {
  var savePaymentButton = document.getElementById('payment-buttons-container')
      .getElementsByTagName('button')[0];

  if (savePaymentButton)
    if (document.getElementById('p_method_mpay24').checked == true)
      savePaymentButton.setAttribute('onClick', 'alert("' + text
          + '");return false;');
    else
      savePaymentButton.setAttribute('onClick', 'payment.save();');

  payment.switchMethod('mpay24');
}