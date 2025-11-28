// notificaciones.js

document.addEventListener('DOMContentLoaded', (event) => {
    // 1. Busca si existe una alerta en la página
    const alerta = document.querySelector('.alerta');

    // 2. Si la encuentra...
    if (alerta) {
        
        // 3. Espera 5 segundos (5000 milisegundos)
        setTimeout(() => {
            // 4. Añade la clase 'fade-out' para que el CSS la anime
            alerta.classList.add('fade-out');
        }, 5000); // <-- Puedes cambiar este tiempo (en milisegundos)

        // 5. (Opcional) Espera 5.5 segundos (5s + 0.5s de anim)
        // y la elimina del todo para que no ocupe espacio.
        setTimeout(() => {
            if (alerta) { // Comprueba si aún existe
               alerta.remove(); 
            }
        }, 5500); // <-- Este debe ser 500ms más que el de arriba
    }
});