@servers(['WS' => 'centos@3.83.8.158','LH' => 'localhost'])

@setup
    $repository = 'https://github.com/lucasmaciel1996/goodsystem.git';
    $app_dir = '/var/www/goodsystem';
    $releases_dir = $app_dir.'/releases';
    $current_dir = $app_dir.'/current';
    $release_p = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release_p;
    $release_version=$release_p;
    $message ='New release implemented on server "Qclass"';
    $slack_hook_teste = 'https://hooks.slack.com/services/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';
    $slack_hook = 'https://hooks.slack.com/services/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';
    $slack_channel = '#deploy_envoy';
    $branch;
@endsetuprelease

@story('deploy',['on'=>['LH','WS'],'parallel'=>true])
    clone_repository
    {{-- 
      update_symlinks
      config_folders
      run_composer
      update_database
      npm_run 
    --}}
  
@endstory

@task('clone_repository')
    @if($branch)
      echo 'Cloning repository ( {{$branch}} )'
      [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
      git clone -b {{$branch}} {{ $repository }} {{ $new_release_dir }}
    @else
      echo 'Cloning repository ( master )'
      [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
      git clone --depth 1  {{ $repository }} {{ $new_release_dir }}
    @endif
@endtask

@task('config_folders')
    echo "Configure folders ({{ $release_p }}) 'execute chmod 777 assets, runtime, images, bower_components, dist, js, themes'"
    cd {{ $new_release_dir }} 
    chmod 777 -R {{ $new_release_dir }}/assets
    chmod 777 -R {{ $new_release_dir }}/runtime
@endtask

@task('update_symlinks')
    echo "Linking current  directory"
    rm  {{ $app_dir }}/current || echo "Não há link (Criando...)"
    ln -nfs  {{$new_release_dir}} {{ $current_dir }}

    echo 'Linking folder ( upload ) '
    ln -nfs {{ $app_dir }}/upload {{ $new_release_dir }}/upload

    echo 'Linking folder ( images ) '
    ln -nfs {{ $app_dir }}/images {{ $new_release_dir }}/images
@endtask

@task('update_database')
    cd {{$current_dir}}/protected
    ./yiic migrate --interactive=0
@endtask
@task('run_composer')
    echo "Starting deployment ({{ $release_p }})"
    cd {{ $new_release_dir }}
    composer update
@endtask

@task('rollback_release')
    cd {{$releases_dir }}
    release_production=` ls * -td |head -1`
    echo "Romove link release production $release_production"
    rm  {{ $app_dir }}/current  
     @if($release)
       old_release={{$release}}
     @else 
       old_release=`ls * -td |head -2 |tail -1`
     @endif
    echo "Rollback release old version $old_release"
    
    ln -nfs  {{$releases_dir }}/$old_release {{ $current_dir }} 
@endtask

@task('checkout_branch')
  cd {{ $new_release_dir }}
  @if($branch)
    echo "Checkout {{$branch}}..."
    git checkout {{$branch}}
  @else
    echo "Checkout 'master'..."
    git checkout master
  @endif

@task ('npm_run')
   echo "Run NPM( {{$new_release_dir}})"
   cd {{$new_release_dir}}
   npm install npm
@endtask

@endtask
@task('help')
  echo "deploy default 'master'                 : params --branch=NOMEBRANCH"
  echo "checkout_branch default 'master'        : params --branch=NOMEBRANCH"
  echo "rollback_release default 'last version' : params --release=VERSION"
@endtask

@error
   echo "Error";
   @slack($slack_hook_teste,$slack_channel,'ERRO AO EXECUTAR DEPLOYER')
   exit (1); /*Or Do any other processing*/
@enderror

@finished
  @slack($slack_hook_teste,$slack_channel,$message)  
@endfinished


