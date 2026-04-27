<?php
    $mysession = $this->session->userdata('user_data')['uname'];
    $count = count($data);
    $today = date("Y-m-d");

    // Helper: render media block (image / audio / video / file)
    $renderMedia = function($row) {
        if (empty($row['media_url'])) return '';
        $url  = base_url() . ltrim($row['media_url'], '/');
        $type = $row['media_type'] ?? 'file';
        $name = htmlspecialchars($row['media_name'] ?? '', ENT_QUOTES);
        if ($type === 'image') {
            return '<a href="'.$url.'" target="_blank"><img src="'.$url.'" style="max-width:220px; max-height:220px; border-radius:8px; display:block;"></a>';
        }
        if ($type === 'audio') {
            // onloadedmetadata: fix WebM duration "0:00 / 0:00" forzando seek a max para que el browser recalcule
            return '<audio controls preload="metadata" style="max-width:220px;" onloadedmetadata="if(this.duration===Infinity){var a=this;a.currentTime=1e101;a.ontimeupdate=function(){a.ontimeupdate=null;a.currentTime=0;};}"><source src="'.$url.'"></audio>';
        }
        if ($type === 'video') {
            return '<video controls preload="metadata" style="max-width:220px; border-radius:8px;"><source src="'.$url.'"></video>';
        }
        return '<a href="'.$url.'" target="_blank" style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; background:#f1f5f9; border-radius:6px; color:#1e293b; text-decoration:none; font-size:12px;">📎 '.($name ?: 'Archivo').'</a>';
    };

    for($i = 0; $i < $count; $i++){
        $row = $data[$i];
        $mediaHtml = $renderMedia($row);
        $hasMsg = trim((string)$row['message']) !== '';

        if($row['sender_message_id'] == $mysession){
        ?>
            <div id="receiver_msg_container">
                <div id="receiver_msg" class="flex flex-col">
                        <?php if ($mediaHtml): ?><div style="margin-bottom:4px;"><?= $mediaHtml ?></div><?php endif; ?>
                        <?php if ($hasMsg): ?><p class="m-0" id="receiver_ptag"><?= htmlspecialchars($row['message']) ?></p><?php endif; ?>
                        <?php
                            $date = date("Y-m-d", strtotime($row['time']));
                            if ($date < $today){
                                $time = date("d-m-Y H:i a", strtotime($row['time']));
                            }else{
                                $time = date("H:i a", strtotime($row['time']));
                            }
                            ?>
                        <p class="m-0 text-xs text-gray-500" id="receiver_pdate"><?= $time ?></p>
                </div>
                <div id="receiver_image" style="background-size: 100% 100%; background-image:url('<?= get_images_path($this->session->userdata('image')) ?>')"></div>
            </div>
        <?php
        }else{
        ?><div id="sender_msg_container">
                <div id="sender_image" style="background-size: 100% 100%; background-image:url('<?= $image ?>')"></div>
                <div id="sender_msg" class="flex flex-col">
                        <?php if ($mediaHtml): ?><div style="margin-bottom:4px;"><?= $mediaHtml ?></div><?php endif; ?>
                        <?php if ($hasMsg): ?><p class="m-0" id="receiver_ptag"><?= htmlspecialchars($row['message']) ?></p><?php endif; ?>
                        <?php
                            $date = date("Y-m-d", strtotime($row['time']));
                            if ($date < $today){
                                $time = date("d-m-Y H:i a", strtotime($row['time']));
                            }else{
                                $time = date("H:i a", strtotime($row['time']));
                            }
                            ?>
                        <p class="m-0 text-xs text-gray-600" id="receiver_pdate"><?= $time ?></p>
                </div>
            </div>
        <?php
        }
    }
?>
