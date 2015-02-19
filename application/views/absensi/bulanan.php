<table name="absen-bulanan">
	<thead>
		<th colspan="<?php echo ($jml_kol+4);?>">Absensi Bulanan untuk Bulan <?php echo $bln?></th>
	</thead>
	<tbody>
		<tr>
			<td rowspan="2">No</td>
			<td rowspan="2">NIP</td>
			<td rowspan="2">Nama</td>
			<td colspan="<?php echo $jml_kol;?>">Tanggal</td>
			<td rowspan="2">Keterangan</td>
		</tr>
		<tr>
			<?php 
			for($i=1; $i<=$jml_kol; $i++){
				echo "<td>$i</td>";
			}
			?>				
		</tr>
		<?php
		$index = 1;
		foreach($abs as $absen){
			echo "<tr>\n";
			echo "<td>".$index."</td>\n";
			echo "<td>".$absen['nip']."</td>\n";
			echo "<td>".$absen['nama']."</td>\n";
			foreach($absen['day'] as $bbs){
				if(array_key_exists('libur',$bbs)){
					echo "<td>".$bbs['libur']."</td>\n";
				}else if(array_key_exists('daily_pct',$bbs)){
					echo "<td>".$bbs['daily_time']."</td>\n";
				}else if(array_key_exists('TL',$bbs)){
					echo "<td>".$bbs['TL']."</td>\n";
				}else{
					echo "<td>".$bbs['daily_second']."</td>\n";
				}
			}
			echo "<td>".$absen['monthly_pct']."</td>\n";
			echo "</tr>\n";
			$index++;
		}
		?>
	</tbody>
</table>
<pre>
<?php var_dump($abs); ?>
</pre>
