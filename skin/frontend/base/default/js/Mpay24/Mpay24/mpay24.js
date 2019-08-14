function setOnClick(text) {
  var radios = document.getElementsByName( 'payment[method]' );
  for( i = 0; i < radios.length; i++ ) {
    if(radios[i].id == 'p_method_mpay24' || radios[i].id.substring(0, 15) != 'p_method_mpay24') {
      radios[i].setAttribute('onClick', "forceSelection('" + text + "', '" + radios[i].id.substring(9) + "'); " + radios[i].onclick);
    }
  }
}

function forceSelection(text, pm) {
  var savePaymentButton = document.getElementById('payment-buttons-container')
      .getElementsByTagName('button')[0];

  if (savePaymentButton) {
    if (document.getElementById('p_method_mpay24').checked == true)
      savePaymentButton.setAttribute('onClick', 'alert("' + text
          + '");return false;');
    else
      savePaymentButton.setAttribute('onClick', 'payment.save();');
  }
  payment.switchMethod(pm);
}