// formulario.js

document.addEventListener('DOMContentLoaded', () => {

    // --- Lógica para Mostrar/Ocultar Contraseña (Registro) ---
    const verPassCheckbox = document.getElementById('ver-contrasena');
    const passInput = document.getElementById('registro-contrasena');

    if (verPassCheckbox && passInput) {
        verPassCheckbox.addEventListener('change', () => {
            if (verPassCheckbox.checked) {
                passInput.type = 'text'; // Mostrar contraseña
            } else {
                passInput.type = 'password'; // Ocultar contraseña
            }
        });
    }

    // --- Lógica para Mostrar/Ocultar Contraseña (Login) ---
    const verPassLoginCheckbox = document.getElementById('ver-contrasena-login');
    const passLoginInput = document.getElementById('login-contrasena');

    if (verPassLoginCheckbox && passLoginInput) {
        verPassLoginCheckbox.addEventListener('change', () => {
            if (verPassLoginCheckbox.checked) {
                passLoginInput.type = 'text'; // Mostrar
            } else {
                passLoginInput.type = 'password'; // Ocultar
            }
        });
    }

    // --- LÓGICA DE AUTOCOMPLETAR NACIONALIDAD ---
    const paisSelect = document.getElementById('pais_nacimiento');
    const nacionalidadInput = document.getElementById('nacionalidad');

    // 1. El mapa de País -> Nacionalidad (en femenino)
    const mapaNacionalidad = {
        "Argentina": "Argentina",
        "Bolivia": "Boliviana",
        "Chile": "Chilena",
        "Colombia": "Colombiana",
        "Costa Rica": "Costarricense",
        "Cuba": "Cubana",
        "Ecuador": "Ecuatoriana",
        "El Salvador": "Salvadoreña",
        "España": "Española",
        "Estados Unidos": "Estadounidense",
        "Guatemala": "Guatemalteca",
        "Honduras": "Hondureña",
        "México": "Mexicana",
        "Nicaragua": "Nicaragüense",
        "Panamá": "Panameña",
        "Paraguay": "Paraguaya",
        "Perú": "Peruana",
        "Puerto Rico": "Puertorriqueña",
        "República Dominicana": "Dominicana",
        "Uruguay": "Uruguaya",
        "Venezuela": "Venezolana"
        // Agrega más si es necesario
    };

    // 2. La función que se activa al cambiar el <select>
    if (paisSelect && nacionalidadInput) {
        
        paisSelect.addEventListener('change', () => {
            const paisElegido = paisSelect.value;
            
            if (mapaNacionalidad[paisElegido]) {
                // Si el país está en el mapa, usa la nacionalidad del mapa
                nacionalidadInput.value = mapaNacionalidad[paisElegido];
            } else if (paisElegido) {
                // Si no está, crea una genérica (ej: "Belice" -> "Beliceña")
                // (Esto es un extra por si añades más países)
                nacionalidadInput.value = paisElegido + "na"; 
            } else {
                // Si seleccionan "-- Selecciona --"
                nacionalidadInput.value = "";
            }
        });
    }

});