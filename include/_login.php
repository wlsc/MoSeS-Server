<!-- Login form -->
<div id="dim_back">
    <form id="lightbox" class="form-signin" method="post" name="login_form" accept-charset="UTF-8">
    	<a class="boxclose" id="boxclose"></a>
        <h2 class="form-signin-heading muted">Please sign in</h2>
<!--         <div class="control-group error"> -->
<!--         <input type="text" class="input-block-level" placeholder="Login" name="login"> -->
        <input type="email" class="input-block-level" placeholder="Email" name="email_login">
<!--         </div> -->
<!--         <div class="control-group success"> -->
        <input type="password" class="input-block-level" placeholder="Password" name="password_login">
<!--         </div> -->
        <label class="text-error" id="login_error_message">Hier ist ein Error!</label>
        <label class="checkbox muted">
        <input id="rememberme" type="checkbox" value="remember-me"> Remember me</label>
        <label class="muted" style="margin-top: 15pt; cursor: default;">
          Not registered? <a href="registration.php">Register!</a>
        </label>
        <label class="muted" style="cursor: default;">
          Password forgotten? <a href="forgot.php">Reset it.</a>
        </label>
        <input type="hidden" name="submit" value="1">
        <button class="btn btn-success" type="submit">Sign in</button>
    </form>
</div>