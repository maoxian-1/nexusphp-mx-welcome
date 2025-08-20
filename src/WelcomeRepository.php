<?php
namespace NexusPlugin\MxWelcome;

use Nexus\Plugin\BasePlugin;
use App\Models\User;
use App\Models\Setting;
use Nexus\Database\NexusDB;

class WelcomeRepository extends BasePlugin
{
    public const ID = 'mx-welcome';
    public const VERSION = '0.1.0';
    public const COMPATIBLE_NP_VERSION = '1.9.0';

    public function install() {}

    public function boot()
    {
        // 注册首页模块 Hook，注入“最近注册用户”模块
        if (function_exists('add_filter')) {
            add_filter('nexus_home_module', [$this, 'injectRecentUsersModule'], 10, 1);
        }

        if (function_exists('do_log')) {
            do_log('mx-welcome plugin booted with recent users module');
        }
    }

    /**
     * 注入首页模块：最近注册用户
     * @param array $modules
     * @return array
     */
    public function injectRecentUsersModule(array $modules): array
    {
        try {
            $welcome = (string) (Setting::get('plugin.mx-welcome.recent_users.welcome', 'Welcome new member'));

            $cacheKey = sprintf('plugin_mx-welcome_recent_users_html_%s', md5($welcome));

            $html = NexusDB::remember($cacheKey, 300, function () use ($welcome) {
                // 读取本地化的“最近消息”标题
                global $lang_index;
                $user = User::query()
                    ->orderBy('added', 'desc')
                    ->take(1)
                    ->get(['id'])[0];

                $nameHtml = function_exists('get_username') ? \get_username($user->id) : (string)$user->id;

                $title = htmlspecialchars($welcome);
                $recentTitle = isset($lang_index['text_recent_news']) ? (string)$lang_index['text_recent_news'] : 'Recent news';
                $recentTitleJs = json_encode($recentTitle, JSON_UNESCAPED_UNICODE);
                $table = <<<HTML
<div id="mx-welcome-recent-users-module">
<h2>{$title}</h2>
<table width="100%"><tr><td>
<div style="text-align: center;">
    <div class="rainbow" style="font-size: 16px; margin: 10px">欢迎我们的新成员: {$nameHtml}</div>
    </div>
</td></tr></table>
</div>
<script>
(function(){
  try {
    var mod = document.getElementById('mx-welcome-recent-users-module');
    if (!mod) return;
    var headers = document.querySelectorAll('h2');
    var targetTitle = {$recentTitleJs};
    for (var i = 0; i < headers.length; i++) {
      var txt = (headers[i].textContent || '').trim();
      if (txt.indexOf(targetTitle) === 0) { // 以“最近消息”开头，兼容管理链接追加
        headers[i].parentNode.insertBefore(mod, headers[i]);
        break;
      }
    }
  } catch (e) {}
})();
</script>
HTML;
                return $table;
            });

            $modules[] = $html;
        } catch (\Throwable $e) {
            if (function_exists('do_log')) {
                \do_log('mx-welcome recent users module error: ' . $e->getMessage());
            }
        }

        return $modules;
    }
}
