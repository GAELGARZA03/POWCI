<?php
// obtener_noticias.php

// ==========================================
// 1. CONFIGURACIÓN
// ==========================================
// ¡IMPORTANTE! Pega aquí abajo la llave que copiaste de NewsAPI.org
$apiKey = '1bfdf44c316b487285669a3e64660417'; 

// ==========================================
// 2. CONEXIÓN A LA API
// ==========================================
// Buscamos noticias de 'futbol', en español, ordenadas por fecha
$url = "https://newsapi.org/v2/everything?q=futbol&language=es&sortBy=publishedAt&pageSize=3&apiKey=$apiKey";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// --- CONFIGURACIÓN BLINDADA PARA LOCALHOST (EVITA ERRORES SSL) ---
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignorar certificado de seguridad local
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);     // Ignorar verificación de host
// Fingimos ser un navegador real para que la API no nos bloquee
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
// -----------------------------------------------------------------

$response = curl_exec($ch);

// --- DEPURACIÓN (Si falla, descomenta las siguientes 3 líneas para ver el error) ---
// if(curl_errno($ch)) { echo 'Error cURL: ' . curl_error($ch); exit; }
// $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// if($httpCode != 200) { echo "Error API (Código $httpCode): $response"; exit; }

curl_close($ch);

// ==========================================
// 3. PROCESAR Y MOSTRAR NOTICIAS
// ==========================================
$data = json_decode($response, true);

if (isset($data['articles']) && count($data['articles']) > 0) {
    echo '<div class="widget-noticias">';
    echo '<h3 style="color:var(--verde-menta); border-bottom:1px solid var(--verde-suave); padding-bottom:10px; margin-top:0;">📰 Noticias en Vivo</h3>';
    
    foreach ($data['articles'] as $noticia) {
        $titulo = $noticia['title'];
        // Filtramos títulos que dicen "[Removed]" (pasa a veces en la versión gratuita)
        if ($titulo === '[Removed]') continue;

        $url = $noticia['url'];
        $imagen = $noticia['urlToImage'];
        
        // Si no hay imagen, usamos una genérica de fútbol o un color
        $imgStyle = $imagen ? "background-image: url('$imagen');" : "background-color: #1e3b27;";
        
        $fuente = $noticia['source']['name'];
        // Formatear fecha (ej: 2023-10-25)
        $fecha = date('d/m H:i', strtotime($noticia['publishedAt']));

        echo "
        <div class='noticia-item' style='margin-bottom:15px; display:flex; gap:10px; align-items:start; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px;'>
            <div style='width:60px; height:60px; flex-shrink:0; border-radius:8px; $imgStyle background-size:cover; background-position:center;'></div>
            
            <div style='flex-grow:1;'>
                <a href='$url' target='_blank' style='color:var(--blanco-suave); text-decoration:none; font-weight:bold; font-size:0.85em; display:block; line-height:1.3; margin-bottom:4px;'>
                    $titulo
                </a>
                <div style='display:flex; justify-content:space-between; font-size:0.75em; color:var(--gris);'>
                    <span>$fuente</span>
                    <span>$fecha</span>
                </div>
            </div>
        </div>";
    }
    echo '</div>';
} else {
    // Si la API responde pero no trae artículos (o da error de límite)
    echo '<div style="padding:10px; text-align:center; color:var(--gris);">';
    echo 'No hay noticias disponibles en este momento.';
    if (isset($data['message'])) {
        echo '<br><small style="color:red;">API Error: ' . $data['message'] . '</small>';
    }
    echo '</div>';
}
?>