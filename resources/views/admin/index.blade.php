@extends('admin.template')

@section('title', 'Linketree Clone - Home')

@section('content')

	<header>
			<h2>Suas Páginas</h2>
	</header>

	<table>
		<thead>
			<tr>
				<th>Título</th>
				<th width="20">Ações</th>
			</tr>
		</thead>
		<tbody>
			@foreach($pages as $page)
				<tr>
					<td>{{$page->op_title}} ({{$page->slug}})</td>
					<td>
						<a href="{{url('/'.$page->slug)}}" target="_blank">Abrir</a>
						<a href="{{url('/admin/'.$page->slug.'/links')}}" target="_blank">Links</a>
						<a href="{{url('/admin/'.$page->slug.'/design')}}" target="_blank">Aparência</a>
						<a href="{{url('/admin/'.$page->slug.'/stats')}}" target="_blank">Estatísticas</a>
					</td>
				</tr>
			@endforeach
		</tbody>

	</table>

@endsection