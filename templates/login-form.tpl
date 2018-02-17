{extends file="form.tpl"}

{block name="form-content"}

    <div class="form-group">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" class="form-control" placeholder="{$config['placeholders']['username']}" value="{$config['debug']['username']}"/>
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" class="form-control" value="{$config['debug']['password']}"/>
    </div>

{/block}

{block name="form-buttons"}

    <div class="form-group">
        <button type="submit" class="btn btn-primary">Login</button>
    </div>

{/block}
