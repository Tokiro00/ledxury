<?php
    $mysession = $this->session->userdata('user_data')['uname'];
    $count = count($data);
    $today = date("Y-m-d");
    for($i = 0; $i < $count; $i++){
        if($data[$i]['sender_message_id'] == $mysession){
        ?>
            <div id="receiver_msg_container">
                <div id="receiver_msg" class="flex flex-col">
                        <p class="m-0" id="receiver_ptag"><?php echo $data[$i]['message'];?></p>
                        <?php 
                            $date = date("Y-m-d", strtotime($data[$i]['time']));//"2010-01-21 00:00:00";

                            if ($date < $today){
                                $time = date("d-m-Y H:i a", strtotime($data[$i]['time']));
                            }else
                            {
                                $time = date("H:i a", strtotime($data[$i]['time']));
                            }
                            ?>
                            
                        <p class="m-0 text-xs text-gray-500" id="receiver_pdate"><?php echo $time;?></p>
                </div>
                <div id="receiver_image" style="background-size: 100% 100%; background-image:url('<?php echo get_images_path($this->session->userdata('image'));?>')"></div>
            </div>
        <?php
        }else{
        ?><div id="sender_msg_container">
                <div id="sender_image" style="background-size: 100% 100%; background-image:url('<?php echo $image;?>')"></div>
                <div id="sender_msg" class="flex flex-col">
                        <p class="m-0" id="receiver_ptag"><?php echo $data[$i]['message'];?></p>
                        <?php 
                            $date = date("Y-m-d", strtotime($data[$i]['time']));//"2010-01-21 00:00:00";

                            if ($date < $today){
                                $time = date("d-m-Y H:i a", strtotime($data[$i]['time']));
                            }else
                            {
                                $time = date("H:i a", strtotime($data[$i]['time']));
                            }
                            ?>
                            
                        <p class="m-0 text-xs text-gray-600" id="receiver_pdate"><?php echo $time;?></p>
                </div>
            </div>
        <?php
        }
    }
?>

