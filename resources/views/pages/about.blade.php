@extends('layouts.shop')

@section('title', 'Giới thiệu')

@section('content')
    <div class="mx-auto max-w-5xl px-4 pt-10 lg:px-8 lg:pt-16">
        <header class="text-center" data-reveal>
            <p class="eyebrow mb-3">Câu chuyện của {{ shop('name') }}</p>
            <h1 class="display mx-auto max-w-3xl text-4xl sm:text-6xl"><em>{{ shop('tagline') }}</em></h1>
            <p class="mx-auto mt-5 max-w-2xl leading-relaxed text-muted">
                Chúng tôi là một xưởng nhỏ làm quần áo bắt đầu từ tranh vẽ. Mỗi mẫu chỉ làm một đợt, số lượng ít, bán hết là thôi.
            </p>
        </header>

        <section class="mt-16 grid gap-6 sm:grid-cols-3" data-reveal>
            @foreach ([
                ['01', 'Làm ít', 'Mỗi đợt chỉ vài chục chiếc. Áo làm ra để mặc, không phải để tồn kho.'],
                ['02', 'Bắt đầu từ tranh', 'Nét vẽ tay đi trước, kiểu dáng chiếc áo đi theo bức vẽ.'],
                ['03', 'Làm gần', 'In và may tại những xưởng chúng tôi biết tên từng người thợ.'],
            ] as [$number, $title, $body])
                <article class="rounded-card bg-surface p-6 shadow-soft">
                    <span class="font-serif text-3xl text-accent italic">{{ $number }}</span>
                    <h2 class="mt-3 font-serif text-xl">{{ $title }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $body }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-16" data-reveal>
            <p class="eyebrow mb-2">Một chiếc áo ra đời thế nào</p>
            <h2 class="display mb-6 text-3xl sm:text-4xl">Từ bản phác đến đường may.</h2>
            <ol class="divide-y divide-line border-y border-line">
                @foreach ([
                    ['Phác', 'Mỗi mẫu bắt đầu bằng một bức vẽ hoặc một vệt cọ trong xưởng. Không chạy theo xu hướng, chỉ có ý tưởng.'],
                    ['In', 'Hình được in lụa bằng tay theo từng đợt nhỏ, mỗi lần một màu, trên vải cotton dày.'],
                    ['May', 'Cắt và hoàn thiện gần xưởng, kiểm từng chiếc trước khi mở bán.'],
                ] as [$title, $body])
                    <li class="flex gap-5 py-5">
                        <span class="font-serif text-2xl text-accent">{{ $loop->iteration }}</span>
                        <div>
                            <h3 class="font-serif text-lg">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-muted">{{ $body }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="mt-16 rounded-card bg-surface-2 px-6 py-12 text-center" data-reveal>
            <h2 class="display text-3xl sm:text-4xl">Tìm chiếc áo <em>của riêng bạn.</em></h2>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a class="btn btn-primary" href="{{ url('/san-pham') }}">Xem sản phẩm</a>
                <a class="btn btn-outline" href="{{ url('/lien-he') }}">Nhắn cho shop</a>
            </div>
        </section>
    </div>
@endsection
