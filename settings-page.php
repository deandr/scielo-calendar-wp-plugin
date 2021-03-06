<?php

function events_settings_page() {
    $last_sync = (int)get_option('scieloevent_last_sync');
    $last_send = get_option('scieloevent_last_send');

    $timezone_offet = get_option( 'gmt_offset' );
    $format_date = get_option('date_format') . ' ' . get_option('time_format');

    if ($last_sync) {
        $last_sync +=  $timezone_offet * 3600;
    }
    if ($last_send){
        $last_send = preg_split("/\|/", $last_send);
        $last_send[0] += $timezone_offet * 3600;
        if ($last_send[1] == 'day'){
            $last_send[1] = 'Eventos do dia';
        }else{
            $last_send[1] = 'Eventos da semana';
        }
    }
?>

<script>
    $ = jQuery;
    $(document).ready(function(){
        $("#show_hide_options").click(function(){
            $("#event_options").toggle();
        });
    });
</script>

<style>
input.url {
    width: 600px;
}
input.port {
    width: 70px;
}
.box{
    margin: 20px 0 20px 0;
}
.last_info{
    background: #fff;
    margin: 10px 0 10px;
    padding: 7px;
    width: 70%;
}
</style>

<div class="wrap">

    <h2>SciELO Calendário</h2>
    <form method="post" action="options.php">
        <?php
            settings_fields( 'event-settings-group' );
            do_settings_sections( 'event-settings-group' );
            $config = get_option('scieloevent_config');
        ?>

        <h3>Sincronização</h3>
        <a href="admin.php?action=sync" class="button button-primary">Sincronizar eventos</a>
        <a href="admin.php?action=reset" class="button button-primary">Apagar eventos</a>
        <div class="last_info">
            <strong>Última sincronização:</strong><em>
            <?php
                if ($last_sync){
                    echo date_i18n($format_date, $last_sync);
                }else{
                    echo 'Não disponível';
                }
            ?>
            </em>
        </div>

        <h3>Mensagens</h3>

        <div class="last_info">
            <strong>Último envio:</strong>

            <?php
                if ($last_send){
                    echo '<em>' . date_i18n($format_date, $last_send[0]) . '</em>  <strong>Tipo: </strong>'  . $last_send[1] . '  <strong>Total de eventos: </strong>' . $last_send[2];
                }else{
                    echo 'Não disponível';
                }
            ?>
        </div>

        <div class="box">
            <a href="#" id="show_hide_options">Mostrar/ocultar configurações</a>
        </div>
        <div id="event_options" style="display: none">
            <hr/>
            <table class="form-table">
                <tbody>
                     <tr valign="top">
                         <th scope="row">URL do arquivo .ics:</th>
                         <td><input type="text" name="scieloevent_config[feed_url]" value="<?php echo $config['feed_url'] ?>" class="regular-text code url"></td>
                     </tr>
                     <tr valign="top">
                         <th scope="row">Nome remetente email:</th>
                         <td><input type="text" name="scieloevent_config[from_name]" value="<?php echo $config['from_name'] ?>" class="regular-text code"></td>
                     </tr>
                     <tr valign="top">
                         <th scope="row">Email do remetente:</th>
                         <td><input type="text" name="scieloevent_config[from_email]" value='<?php echo $config['from_email'] ?>' class="regular-text code"></td>
                     </tr>
                     <tr valign="top">
                         <th scope="row">Nome do destinatario:</th>
                         <td><input type="text" name="scieloevent_config[to_name]" value='<?php echo $config['to_name'] ?>' class="regular-text code"></td>
                     </tr>
                     <tr valign="top">
                         <th scope="row">Email do destinatario:</th>
                         <td><input type="text" name="scieloevent_config[to_email]" value='<?php echo $config['to_email'] ?>' class="regular-text code"></td>
                     </tr>
                     <tr valign="top">
                         <th scope="row">Email de cópia (debug):</th>
                         <td><input type="text" name="scieloevent_config[bcc_email]" value='<?php echo $config['bcc_email'] ?>' class="regular-text code"></td>
                     </tr>

                     <tr valign="top">
                         <th scope="row">Servidor SMTP:</th>
                         <td><input type="text" name="scieloevent_config[smtp_server]" value='<?php echo $config['smtp_server'] ?>' class="regular-text code"></td>
                     </tr>

                     <tr valign="top">
                         <th scope="row">Usuário SMTP:</th>
                         <td><input type="text" name="scieloevent_config[smtp_user]" value='<?php echo $config['smtp_user'] ?>' class="regular-text code"></td>
                     </tr>
                     <tr valign="top">
                         <th scope="row">Senha SMTP:</th>
                         <td><input type="text" name="scieloevent_config[smtp_password]" value='<?php echo $config['smtp_password'] ?>' class="regular-text code"></td>
                     </tr>
                     <tr valign="top">
                         <th scope="row">Porta SMTP:</th>
                         <td><input type="text" name="scieloevent_config[smtp_port]" value='<?php echo $config['smtp_port'] ?>' class="regular-text code port"></td>
                     </tr>
                </tbody>
            </table>

            <?php submit_button(); ?>
        </div>

    </form>
</div>
<?php
/* debug wp schedule events
echo '<pre>'; print_r( _get_cron_array() ); echo '</pre>';
*/
}
?>
