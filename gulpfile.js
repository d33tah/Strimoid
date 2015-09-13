var elixir = require('laravel-elixir')
var bower = require('main-bower-files')
require('laravel-elixir-riot')

elixir(function(mix) {
    console.log(bower('**/*.css'))

    mix.styles(bower('**/*.css'), 'public/assets/js/vendor.css', '/')
       .scripts(bower('**/*.js'), 'public/assets/js/vendor.js', '/')
       .sass('**/*.(sass|scss)', 'public/assets/css/all.css')
       .riot('**/*.tag', 'public/assets/js/riot.js')
       .scripts([
            'plugins/*.js',
            'modules/*.js',
            'lara.js'
       ], 'public/assets/js', 'resources/assets/js')
       .version([
            'assets/css/all.css',
            'assets/css/vendor.css',
            'assets/js/all.js',
            'assets/js/riot.js',
            'assets/js/vendor.js'
       ])
});

elixir(function(mix) {
    mix.copy('bower_components/bootstrap/dist/fonts', 'public/assets/fonts')
       .copy('bower_components/bootstrap/dist/fonts', 'public/build/assets/fonts')
       .copy('bower_components/font-awesome/fonts', 'public/assets/fonts')
       .copy('bower_components/font-awesome/fonts', 'public/build/assets/fonts')
});
