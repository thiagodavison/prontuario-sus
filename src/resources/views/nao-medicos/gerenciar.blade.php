@extends('layouts.app')

@section('titulo', 'Gerenciar profissional')

@section('lateral')
    {{--  <li><a href="#">Item person</a></li>  --}}
@endsection

@section('conteudo')

	<a href="{{ url('nao-medicos') }}" class="btn secundaria">Voltar</a>

    <p style="text-align:center">
        @if(session('msg'))
            <span class="texto-verde">
                {{ session('msg') }}
            </span>
        @endif

         @if(session('erro'))
            <span class="texto-vermelho">
                {{ session('erro') }}
            </span>
        @endif
    </p>

    <section class="cartao" id="imprimir">
        <header>
            {{ $medico->usuario->nome }}
        </header>
        <article>
            <p>
            	<strong>Email: </strong>
            	{{ $medico->usuario->email }}
            </p>

            <p>
            	<strong>CPF: </strong>
            	{{ $medico->usuario->cpf }}
            </p>

            <p>
            	<strong>Nascimento: </strong>
            	{{ date('d/m/Y', strtotime($medico->usuario->nascimento)) }}
            </p>

            <p>
            	<strong>Status: </strong>
            	{{ ($medico->usuario->valido) ? 'Ativo' : 'Inativo' }}
            </p>

            <p>
                <strong>N.Conselho: </strong>
                {{ $medico->conselho }}
            </p>

            <p>
                <strong>Especialidade: </strong>
                {{ $medico->especialidade }}
            </p>

            <p>
                <strong>Cargo: </strong>
                {{ $medico->cargo }}
            </p>

            <p>
                <strong>Telefone: </strong>
                {{ $medico->telefone }}
            </p>


            <hr>

            <p style="text-align: right;">
            	<small>
            		<strong>Criado em: </strong>
	            	{{ date('d/m/Y á\s H:i', strtotime($medico->usuario->created_at)) }}
					|
	            	<strong>Última edição: </strong>
	            	{{ date('d/m/Y á\s H:i', strtotime($medico->usuario->updated_at)) }}
            	</small>
            </p>

        </article>
        <footer style="text-align: right">

        	<a href="{{ url('usuarios/apagar/' . $medico->usuario->id) }}" onclick="return confirm('Deseja apagar?')" class="btn vermelho">Apagar</a>
            <a href="{{ url('nao-medicos/editar/' . $medico->id) }}" class="btn amarelo">Editar</a>

            @if($medico->usuario->valido)
                <a href="{{ url('usuarios/bloquear/' . $medico->usuario->id) }}" class="btn azul">Bloquear</a>
            @else
                <a href="{{ url('usuarios/desbloquear/' . $medico->usuario->id) }}" class="btn verde">Desbloquear</a>
            @endif


			<a href="{{ url('usuarios/redefinir/' . $medico->usuario->id) }}" class="btn verde">Redefinir senha</a>

            @if($medico->historico)
                <a href="{{ url('nao-medicos/historico/' . $medico->id) }}" class="btn vermelho">B. histórico</a>
            @else
                <a href="{{ url('nao-medicos/historico/' . $medico->id) }}" class="btn vermelho">D. histórico</a>
            @endif

            <button onclick="printDiv('imprimir')" class="btn verde oculta-tel" >Imprimir</button>

        </footer>
    </section>

@endsection