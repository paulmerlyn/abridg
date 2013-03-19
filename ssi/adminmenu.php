<!-- Menu for the Account Manager UI. Note that the id attributes are used to disable these hyperlinks in assign.php when the 'semitofullbox' is displayed for a semiregistered owner. -->
<div style="margin-top: 20px; margin-bottom: 20px; text-align: center; color: #F04040; font-family: Geneva, Arial, sans-serif; font-weight: bold;">
<table cellpadding="0" cellspacing="0" align="center">
<td>
<a id="uploadlink" <?php if ($_SERVER["REQUEST_URI"] == '/upload.php') echo 'class="activemenuitem"'; ?> style="font-size: 10px;" href="/upload.php">Upload</a>
</td>
<td align="center" width="20">|</td>
<td>
<a id="addlink" <?php if ($_SERVER["REQUEST_URI"] == '/addassociate.php') echo 'class="activemenuitem"'; ?> style="font-size: 10px;" href="/addassociate.php">Add</a>
</td>
<td align="center" width="20">|</td>
<td>
<a id="assignlink" <?php if ($_SERVER["REQUEST_URI"] == '/assign.php') echo 'class="activemenuitem"'; ?> style="font-size: 10px;" href="/assign.php">Assign</a>
</td>
<td align="center" width="20">|</td>
<td>
<a id="managelink" <?php if ($_SERVER["REQUEST_URI"] == '/manage.php') echo 'class="activemenuitem"'; ?> style="font-size: 10px;" href="/manage.php">Manage</a>
</td>
</tr>
</table>
</div>