<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; //Adicionamos
use Illuminate\Validation\Rule; //Chamamos para validar o campo border no método de submit do form de criar link

use App\Models\User;
use App\Models\Page;
use App\Models\Link;

class AdminController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth',['except'=>[
			'login',
			'loginAction',
			'register',
			'registerAction'
		]]);
	}
    
    //Abrir a view de login
    public function login(Request $request)
    {
    	return view('admin\login', 
    		['error' => $request->session()->get('error')]
    	);
    }

    //Logar um usuário
    public function loginAction(Request $request)
    {
    	//Recebendo os dados que vieram do form
    	$creds = $request->only('email','password');

    	//Se o usuário já estiver logado, ele já vai para a rota /admin (O Auth::attempt() faz o login)
    	if(Auth::attempt($creds))
    	{
    		return redirect('/admin');
    	}
    	else
    	{
    		//configurando uma mensagem para o caso de erro
    		$request->session()->flash('error', 'E-mail e/ou senha não conferem.');
    		//Redirecionando para essa rota no caso de erro
    		return redirect('/admin/login');
    	}
    }

    //Abrir a view de registro
    public function register(Request $request)
    {
    	return view('admin\register', 
            ['error' => $request->session()->get('error')]
        );
    }

    //Registrar um usuário
    public function registerAction(Request $request)
    {
        //Recebendo os dados que vieram do form
        $creds = $request->only('email','password');

        //Verificando se o email já existe
        $hasEmail = User::where('email', $creds['email'])->count();

        if($hasEmail === 0) {
            //Cadastrando o usuário
            $newUser = new User();
            $newUser->email = $creds['email'];
            $newUser->password = password_hash($creds['password'], PASSWORD_DEFAULT);
            $newUser->save();

            //Após cadastrar, já vamos logar o usuário
            Auth::login($newUser);
            return redirect('/admin');
        }
        else {
            //configurando uma mensagem de erro para caso já exista um usuário com esse email
            $request->session()->flash('error', 'Já existe um usuário com esse E-mail.');
            //Redirecionando para essa rota de cadastro
            return redirect('/admin/register');
        }

        //Se o usuário já estiver logado, ele já vai para a rota /admin (O Auth::attempt() faz o login)
        if(Auth::attempt($creds)) {
            return redirect('/admin');
        }
        else {
            //configurando uma mensagem para o caso de erro
            $request->session()->flash('error', 'E-mail e/ou senha não conferem.');
            //Redirecionando para essa rota no caso de erro
            return redirect('/admin/login');
        }
    }

    //Método de logout
    public function logout(Request $request)
    {
       //Fazendo o processo de logout
        Auth::logout();

        return redirect('/admin');

    }


    public function index()
    {
        //Pegando o usuário logado
        $user = Auth::user();

        //Pegando as páginas que pertencem ao usuário logado
        $pages = Page::where('id_user', $user->id)->get();

        //Mandando para a view
        return view('admin/index', [
            'pages' => $pages          
        ]);
    }

    public function pageLinks($slug)
    {  
        //Pegando o usuário logado
        $user = Auth::user();

        //Pra proteger que o usuário não acesse a página de outro usuário
        $page = Page::where('slug', $slug)->where('id_user', $user->id)->first();

        if($page)
        {
            //Pegando os links de determinada página
            $links = Link::where('id_page', $page->id)->orderBy('order','ASC')->get();

            //Se a  página é realmente do usuário logado, nós direcionamos pra ela
            return view('admin/page-links', [
                'menu' => 'links',
                'page' => $page,
                'links' => $links
            ]);
        }
        else
        {
            //Se a página não for do usuário logado, direcionamos para a página inicial
            return redirect('/admin');
        }

       
    }

    //Método que faz a troca da ordem dos links
    public function linkOrderUpdate($linkId, $pos)
    {  
        $user = Auth::user();

        /* Verificar se o link pertence a uma página do usuário logado */


        /* Lógica para trocar o order no banco de dados */
        //- Verificando se o item subiu ou desceu.
        //- Se subiu (Então jogaremos os próximos itens a partir do item que ele gostaria de ir)
        //- Depois que jogamos os itens pra baixo, substituímos o item que queremos mudar e por fim atualizamos todos os link
        //- Se desceu, depois de jogarmos os itens pra baixo, jogamos todos os itens anteriores pra cima. 
        //- Substituimos o item que quero mudar e por fim atualizo os links.


        //Pegando o link
        $link = Link::find($linkId);

        //Verificar se o link pertence a uma página do usuário logado
        //Pegando a lista das páginas do usuário
        $myPages = [];
        $myPagesQuery = Page::where('id_user', $user->id)->get();
        foreach($myPagesQuery as $pageItem)
        {
            $myPages[] = $pageItem->id;
        }

        //Verificando se o link pertence a uma página do usuário
        if(in_array($link->id_page, $myPages))
        {
            if($link->order > $pos)
            {
                //Subiu item
                //Jogando os próximos pra baixo
                $afterLinks = Link::where('id_page', $link->id_page)
                              ->where('order', '>=', $pos)
                              ->get();

                //Jogou todos os próximos mais pra frente   
                foreach($afterLinks as $afterLink)
                {
                    $afterLink->order++;
                    $afterLink->save();
                }

            }
            elseif($link->order < $pos)
            {
                //Desceu item
                //Jogando os anteriores pra cima
                $beforeLinks = Link::where('id_page', $link->id_page)
                               ->where('order', '<=', $pos)
                               ->get();
                //Jogou todos os próximos mais pra frente   
                foreach($beforeLinks as $beforeLink)
                {
                    $beforeLink->order--;
                    $beforeLink->save();
                }
            }

            //Posicionando o item
            $link->order = $pos;
            $link->save();

            //Corrigindo as posições
            //Pegando os itens na ordem correta
            $allLinks = Link::where('id_page', $link->id_page)
                        ->orderBy('order', 'ASC')
                        ->get();
            //Corrigindo essa ordem
            foreach($allLinks as $linkKey => $linkItem)
            {   
                //O linkKey sempre vai vir na ordem certinha. É o índice de um array, basicamente. Então o primeiro vai ser 0, o segundo 1 ... (corrige a situação pra não ficar -1 e tals)
                $linkItem->order = $linkKey;
                $linkItem->save();
            }
        }

        return []; //Esse método não tem retorno para o usuário (retorno visual)
    }

    //Renderiza a tela de criação de link
    public function newLink($slug)
    {   
        //Pegando o usuário logado
        $user = Auth::user();
        //Pegando a página do usuário logado com determinado slug
        $page = Page::where('id_user', $user->id)->where('slug', $slug)->first();

        //Se achou a página, carregará a view com o formulário para criarmos um novo link
        if($page)
        {
            //Mandando para a view
            return view('admin/page-new-or-editlink', [
                 'menu' => 'links',
                 'page' => $page
            ]);
        }
        else
        {
            return redirect('/admin');
        }

    }

        //Método da criação do link
    public function newLinkAction($slug, Request $request)
    {   
        //Pegando o usuário logado
        $user = Auth::user();
        //Pegando a página do usuário logado com determinado slug
        $page = Page::where('id_user', $user->id)->where('slug', $slug)->first();

        //Se achou a página
        if($page)
        {
            //Validando os campos
            $fields = $request->validate([
                'status' => ['required', 'boolean'],
                'title' => ['required', 'min:2'],
                'href' => ['required', 'url'],
                'op_bg_color' => ['required', 'regex:/^[#][0-9A-F]{3,6}$/i'], //Regex para validar o campo color
                'op_text_color' => ['required', 'regex:/^[#][0-9A-F]{3,6}$/i'], //Regex para validar o campo color
                'op_border_type' => ['required', Rule::in(['square', 'rounded'])]
            ]);

            //Pegando o total de links que temos daquela página (para colocarmos o order da maneira certa)
            $totalLinks = Link::where('id_page', $page->id)->count();

            //Criando o novo link
            $newLink = new Link();
            $newLink->id_page = $page->id;
            $newLink->status = $fields['status'];
            $newLink->order = $totalLinks;
            $newLink->title = $fields['title'];
            $newLink->href = $fields['href'];
            $newLink->op_bg_color = $fields['op_bg_color'];
            $newLink->op_text_color = $fields['op_text_color'];
            $newLink->op_border_type = $fields['op_border_type'];
            $newLink->save();

            //Redirecionando para a tela de listagem dos links
            return redirect('/admin/'.$page->slug.'/links');
        }
        else
        {
            return redirect('/admin');
        }
    }

    //Renderiza a tela de edição de link
    public function editLink($slug, $linkId)
    {   
        //Pegando o usuário logado
        $user = Auth::user();
        //Pegando a página do usuário logado com determinado slug
        $page = Page::where('id_user', $user->id)->where('slug', $slug)->first();

        //Se achou a página, carregará a view com o formulário para criarmos um novo link
        if($page)
        {
            //Verificar se o link existe e faz parte dessa página
            $link = Link::where('id_page', $page->id)
                    ->where('id', $linkId)
                    ->first();

            if($link)
            {
                 //Mandando para a view
                return view('admin/page-new-or-editlink', [
                     'menu' => 'links',
                     'page' => $page,
                     'link' => $link
                ]);
            }
            else
            {
               
            }
        }
      
        return redirect('/admin');
    }

    //Método de edição do linl
    public function editLinkAction($slug, $linkId, Request $request)
    {   
        //Pegando o usuário logado
        $user = Auth::user();
        //Pegando a página do usuário logado com determinado slug
        $page = Page::where('id_user', $user->id)->where('slug', $slug)->first();

        //Se achou a página, carregará a view com o formulário para criarmos um novo link
        if($page) {
            //Verificar se o link existe e faz parte dessa página
            $link = Link::where('id_page', $page->id)
                    ->where('id', $linkId)
                    ->first();

            if($link) {
               //Validando os campos
                $fields = $request->validate([
                    'status' => ['required', 'boolean'],
                    'title' => ['required', 'min:2'],
                    'href' => ['required', 'url'],
                    'op_bg_color' => ['required', 'regex:/^[#][0-9A-F]{3,6}$/i'], //Regex para validar o campo color
                    'op_text_color' => ['required', 'regex:/^[#][0-9A-F]{3,6}$/i'], //Regex para validar o campo color
                    'op_border_type' => ['required', Rule::in(['square', 'rounded'])]
                ]);

                $link->status = $fields['status'];              
                $link->title = $fields['title'];
                $link->href = $fields['href'];
                $link->op_bg_color = $fields['op_bg_color'];
                $link->op_text_color = $fields['op_text_color'];
                $link->op_border_type = $fields['op_border_type']; 
                $link->save();

                //Redirecionando para a tela de listagem dos links
                return redirect('/admin/'.$page->slug.'/links');
            }
           
        }
      
        return redirect('/admin');
    }


    //Método de deletar um link
    public function delLink($slug, $linkId)
    {   
        //Pegando o usuário logado
        $user = Auth::user();
        //Pegando a página do usuário logado com determinado slug
        $page = Page::where('id_user', $user->id)->where('slug', $slug)->first();

        
        //Se achou a página, carregará a view com o formulário para criarmos um novo link
        if($page) {
            //Verificar se o link existe e faz parte dessa página
            $link = Link::where('id_page', $page->id)
                    ->where('id', $linkId)
                    ->first();

            if($link) {
                $link->delete();

                //Corrigindo as posições
                //Pegando os itens na ordem correta
                $allLinks = Link::where('id_page', $page->id)
                            ->orderBy('order', 'ASC')
                            ->get();

                //Corrigindo essa ordem
                foreach($allLinks as $linkKey => $linkItem)
                {   
                    //O linkKey sempre vai vir na ordem certinha. É o índice de um array, basicamente. Então o primeiro vai ser 0, o segundo 1 ... (corrige a situação pra não ficar -1 e tals)
                    $linkItem->order = $linkKey;
                    $linkItem->save();
                }

                return redirect('/admin/'.$page->slug.'/links');
            }
           
        }
      
        return redirect('/admin');
    }


    public function pageDesign($slug)
    {  
        //Mandando para a view
        return view('admin/page-design', [
             'menu' => 'design'
        ]);
    }

    public function pageStats($slug)
    {  
        //Mandando para a view
        return view('admin/page-stats', [
             'menu' => 'stats'
        ]);
    }
}
