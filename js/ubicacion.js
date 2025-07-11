navigator.geolocation.getCurrentPosition(function(position) {
  document.getElementById('latitud').value = position.coords.latitude;
  document.getElementById('longitud').value = position.coords.longitude;
}, function(error) {
  document.getElementById('error').textContent = "Por favor, permita el acceso a su ubicación.";
});