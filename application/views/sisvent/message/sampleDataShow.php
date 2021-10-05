<?php
$count = count($data);
for ($i=0; $i < $count ; $i++) {

	if($data[$i]->user_status == 'active'){
		?>
			<div class='innerBox'>
					<div class='user px-3 py-2'>
						<div id='avtar_and_details' class=''>
							<div id='user_avtar' style="background-image: url('<?php echo get_images_path($data[$i]->picture_url);?>'); background-size: 100% 100%;">
								<div id='online'></div>
								<input type='hidden' name='hdn' id='hidden_id' value="<?php echo $data[$i]->idUser;?>">
							</div>
							<div id='user_details' class='px-2'>
								<h6 class='m-0' id='name'><?php echo $data[$i]->name?></h6>
								<p class='m-0' id="title">
									<?php
										$output = "";
										for($j = 0; $j < count($last_msg); $j++){
											if($data[$i]->idUser == $last_msg[$j]['sender_id']){

												$output = ($last_msg[$j]['message']);

											}elseif($data[$i]->idUser == $last_msg[$j]['receiver_id']){

												$output = "Tú : ".$last_msg[$j]['message'];
												
											}else{
												// $output = "No message yet..";
												
											}
										}
										if(strlen($output) > 20){
											echo substr($output,0,20)."...";
										}else{
											echo $output;
										}
									?>
								</p>
							</div>
						</div>
						<div class="flex flex-col">
							<?php if($data[$i]->nrmsgs > 0): ?>
								<p class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-600 bg-red-100 rounded-full dark:text-red-100 dark:bg-red-600">
							<?php
								
								echo $data[$i]->nrmsgs;
							?>
							</p>
							<?php endif; ?>
							<p id="time">
							<?php
								$messageTime = "";
								for($j = 0; $j < count($last_msg); $j++){
									if($data[$i]->idUser == $last_msg[$j]['sender_id'] || $data[$i]->idUser == $last_msg[$j]['receiver_id']){
										$messageTime = $last_msg[$j]['time'];
									}
								}
								echo $messageTime;
							?>
							</p>
						</div>
					</div>
				</div>
		<?php
	}else{
		?>
		<div class='innerBox'>
					<div class='user px-3 py-2'>
						<div id='avtar_and_details' class=''>
							<div id='user_avtar' style="background-image: url('<?php echo get_images_path($data[$i]->picture_url);?>'); background-size: 100% 100%;">
								<input type='hidden' name='hdn' id='hidden_id' value="<?php echo $data[$i]->idUser;?>">
							</div>
							<div id='user_details' class='px-2'>
								<h6 class='m-0' id='name'><?php echo $data[$i]->name?></h6>
								<p class='m-0' id="message">
								<?php
										$output = "";
										for($j = 0; $j < count($last_msg); $j++){
											
											if($data[$i]->idUser == $last_msg[$j]['sender_id']){

												$output = $last_msg[$j]['message'];

											}elseif($data[$i]->idUser == $last_msg[$j]['receiver_id']){

												$output = "Tú : ".$last_msg[$j]['message'];
												
											}else{
												// $output = "No message yet..";
												
											}
											
										}
										if(strlen($output) > 20){
											echo substr($output,0,20)."...";
										}else{
											echo $output;
										}
										
									?>
								</p>
							</div>
						</div>
						<div class="flex flex-col">
							<?php if($data[$i]->nrmsgs > 0): ?>
								<p class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-600 bg-red-100 rounded-full dark:text-red-100 dark:bg-red-600">
							<?php
								
								echo $data[$i]->nrmsgs;
							?>
							</p>
							<?php endif; ?>
							<p id="time">
							<?php
								$messageTime = "";
								for($j = 0; $j < count($last_msg); $j++){
									if($data[$i]->idUser == $last_msg[$j]['sender_id'] || $data[$i]->idUser == $last_msg[$j]['receiver_id']){
										$messageTime = $last_msg[$j]['time'];
									}
								}
								echo $messageTime;
							?>
							</p>
						</div>
					</div>
				</div>
		<?php
	}
}
	?>