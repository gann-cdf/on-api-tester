{extends file="page.tpl"}

{block name="content"}

    <div class="container">
        <p>Documentation of the Blackbaud ON API can be found <a href="http://on-api.developer.blackbaud.com/docs/">here</a>. In these tests, capitalization reflects what is found in that documentation, which may or may not reflect actual, inconsistent case-sensitivity within the API itself</p>
    </div>

    <div class="container">
        {foreach $tests as $test}
            <row>
                {foreach $test as $endpoint => $output}
                    <col>
                        <h3>{$endpoint}</h3>
                        <pre>{$output}</pre>
                    </col>
                {/foreach}
            </row>
        {/foreach}
    </div>

{/block}
