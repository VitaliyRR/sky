import {
  ArrowRight,
  Blocks,
  Check,
  CloudCog,
  CodeXml,
  Headphones,
  LockKeyhole,
  MapPin,
  Network,
  Phone,
  Send,
  ShieldCheck,
  Store,
  Terminal,
  WalletCards,
  Zap,
} from 'lucide-react';

const audiences = [
  {
    icon: Terminal,
    number: '01',
    title: 'Платёжным агентам',
    text: 'Переводите терминалы и операторские точки на единую платформу с удалённым управлением.',
  },
  {
    icon: Network,
    number: '02',
    title: 'Провайдерам услуг',
    text: 'Расширяйте сеть приёма платежей и автоматизируйте обмен данными и отчётность.',
  },
  {
    icon: Blocks,
    number: '03',
    title: 'Поставщикам товаров',
    text: 'Подключайте каталог, принимайте заказы и увеличивайте продажи через точки SkySend.',
  },
  {
    icon: Store,
    number: '04',
    title: 'Торговым сетям',
    text: 'Добавляйте востребованные сервисы в кассовую зону и создавайте новый поток клиентов.',
  },
];

const features = [
  {
    icon: Zap,
    title: 'Высокая скорость',
    text: 'Платежи проходят быстро, а состояние сети видно в реальном времени.',
    className: 'feature-card feature-card--blue',
  },
  {
    icon: ShieldCheck,
    title: 'Защита данных',
    text: 'Безопасная передача информации и контроль операций на каждом этапе.',
    className: 'feature-card',
  },
  {
    icon: CloudCog,
    title: 'Удалённое управление',
    text: 'Настройки, обновления и мониторинг оборудования — из единого кабинета.',
    className: 'feature-card feature-card--wide',
  },
  {
    icon: Headphones,
    title: 'Поддержка 24/7',
    text: 'Помогаем участникам системы и плательщикам круглосуточно.',
    className: 'feature-card feature-card--dark',
  },
];

const software = [
  ['FastPay', 'ПО платёжного терминала'],
  ['Windows / Linux', 'Рабочее место агента'],
  ['Android', 'Мобильное рабочее место'],
  ['XML', 'Шлюз для интеграции'],
  ['POS', 'ПО для кассовых терминалов'],
];

export default function Home() {
  return (
    <main>
      <header className="site-header">
        <div className="shell header-inner">
          <a className="brand" href="#top" aria-label="SkySend — на главную">
            <img
              src="/images/skysend-logo.png"
              alt="SkySend"
              width="158"
              height="100"
            />
          </a>
          <nav className="main-nav" aria-label="Основная навигация">
            <a href="#solutions">Решения</a>
            <a href="#benefits">Преимущества</a>
            <a href="#software">Программы</a>
            <a href="#contacts">Контакты</a>
          </nav>
          <div className="header-actions">
            <a className="phone-link" href="tel:+78005552536">
              8 800 555-25-36
            </a>
            <a
              className="button button--compact button--outline"
              href="https://cluster.skysend.ru/"
              target="_blank"
              rel="noreferrer"
            >
              Войти
            </a>
          </div>
        </div>
      </header>

      <section className="hero" id="top">
        <img
          className="hero-image"
          src="/images/payment-network-hero.jpg"
          alt=""
          width="1900"
          height="828"
          fetchPriority="high"
        />
        <div className="hero-shade" />
        <div className="shell hero-inner">
          <div className="hero-copy">
            <p className="eyebrow">
              <span /> Платёжная инфраструктура · с 2006 года
            </p>
            <h1>
              Принимайте платежи.
              <br />
              <em>Развивайте сеть.</em>
            </h1>
            <p className="hero-lead">
              SkySend объединяет терминалы, точки оплаты и поставщиков услуг в
              одной надёжной системе.
            </p>
            <div className="hero-actions">
              <a className="button button--primary" href="#contacts">
                Подключиться к SkySend <ArrowRight aria-hidden="true" />
              </a>
              <a
                className="button button--ghost"
                href="https://cluster.skysend.ru/"
                target="_blank"
                rel="noreferrer"
              >
                Личный кабинет
              </a>
            </div>
            <div className="hero-note">
              <LockKeyhole aria-hidden="true" />
              Защищённая передача данных и круглосуточная поддержка
            </div>
          </div>
        </div>
        <div className="shell stats" aria-label="Ключевые показатели SkySend">
          <div>
            <strong>5 000+</strong>
            <span>поставщиков услуг</span>
          </div>
          <div>
            <strong>24/7</strong>
            <span>поддержка системы</span>
          </div>
          <div>
            <strong>до 50%</strong>
            <span>снижение расходов</span>
          </div>
          <div>
            <strong>до 20%</strong>
            <span>рост доходов сети</span>
          </div>
        </div>
      </section>

      <section className="section section--light" id="solutions">
        <div className="shell">
          <div className="section-heading split-heading">
            <div>
              <p className="section-label">Решения</p>
              <h2>Одна система — разные модели бизнеса</h2>
            </div>
            <p>
              SkySend помогает участникам платёжного рынка подключаться быстрее,
              работать стабильнее и управлять сетью без лишней рутины.
            </p>
          </div>
          <div className="audience-grid">
            {audiences.map(({ icon: Icon, number, title, text }) => (
              <article className="audience-card" key={title}>
                <div className="card-topline">
                  <span className="icon-chip">
                    <Icon aria-hidden="true" />
                  </span>
                  <span className="card-number">{number}</span>
                </div>
                <h3>{title}</h3>
                <p>{text}</p>
                <a href="#contacts">
                  Узнать подробнее <ArrowRight aria-hidden="true" />
                </a>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="section section--ink" id="benefits">
        <div className="shell">
          <div className="section-heading section-heading--inverse">
            <p className="section-label">Преимущества</p>
            <h2>Всё, что нужно для стабильной платёжной сети</h2>
          </div>
          <div className="feature-grid">
            {features.map(({ icon: Icon, title, text, className }) => (
              <article className={className} key={title}>
                <Icon aria-hidden="true" />
                <div>
                  <h3>{title}</h3>
                  <p>{text}</p>
                </div>
              </article>
            ))}
          </div>
          <div className="benefit-footer">
            <p>Без скрытых комиссий</p>
            <p>Автоматическая отчётность</p>
            <p>Единый центр управления</p>
          </div>
        </div>
      </section>

      <section className="section section--process">
        <div className="shell process-layout">
          <div className="process-intro">
            <p className="section-label">Подключение</p>
            <h2>От заявки до первого платежа</h2>
            <p>
              Команда SkySend помогает выбрать решение, подключить оборудование
              и запустить приём платежей.
            </p>
            <a className="text-link" href="#contacts">
              Обсудить подключение <ArrowRight aria-hidden="true" />
            </a>
          </div>
          <ol className="steps">
            <li>
              <span>1</span>
              <div>
                <h3>Оставьте заявку</h3>
                <p>Расскажите о своей задаче, сети и текущем оборудовании.</p>
              </div>
            </li>
            <li>
              <span>2</span>
              <div>
                <h3>Настройте интеграцию</h3>
                <p>Подключим готовое ПО или обмен данными по XML-протоколу.</p>
              </div>
            </li>
            <li>
              <span>3</span>
              <div>
                <h3>Запустите платежи</h3>
                <p>Проверим работу и останемся на связи после запуска.</p>
              </div>
            </li>
          </ol>
        </div>
      </section>

      <section className="section software-section" id="software">
        <div className="shell">
          <div className="section-heading split-heading">
            <div>
              <p className="section-label">Программы</p>
              <h2>Инструменты для каждой точки приёма платежей</h2>
            </div>
            <div className="software-mark" aria-hidden="true">
              <CodeXml />
            </div>
          </div>
          <div className="software-list">
            {software.map(([name, description], index) => (
              <div className="software-row" key={name}>
                <span className="software-index">
                  {String(index + 1).padStart(2, '0')}
                </span>
                <strong>{name}</strong>
                <span>{description}</span>
                <Check aria-hidden="true" />
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="section contact-section" id="contacts">
        <div className="shell contact-card">
          <div className="contact-copy">
            <p className="section-label">Начните работу</p>
            <h2>Подключите SkySend к своему бизнесу</h2>
            <p>
              Позвоните или напишите в Telegram — специалисты ответят на вопросы
              и подберут подходящий вариант подключения.
            </p>
            <div className="contact-actions">
              <a className="button button--light" href="tel:+78005552536">
                <Phone aria-hidden="true" /> 8 800 555-25-36
              </a>
              <a
                className="button button--contact-outline"
                href="https://t.me/infsysgroup"
                target="_blank"
                rel="noreferrer"
              >
                <Send aria-hidden="true" /> Написать в Telegram
              </a>
            </div>
          </div>
          <div className="contact-details">
            <div>
              <MapPin aria-hidden="true" />
              <p>
                <span>Адрес</span>
                350049, г. Краснодар,
                <br /> ул. Монтажников, д. 1/4
              </p>
            </div>
            <div>
              <WalletCards aria-hidden="true" />
              <p>
                <span>Для действующих участников</span>
                <a
                  href="https://cluster.skysend.ru/"
                  target="_blank"
                  rel="noreferrer"
                >
                  Войти в систему
                </a>
              </p>
            </div>
          </div>
        </div>
      </section>

      <footer className="site-footer">
        <div className="shell footer-inner">
          <div className="footer-brand">
            <img
              src="/images/skysend-logo.png"
              alt="SkySend"
              width="158"
              height="100"
            />
            <p>Система приёма платежей</p>
          </div>
          <p>© 2006–2026 Группа компаний «Информ-Системы»</p>
          <a href="https://www.isg.dev" target="_blank" rel="noreferrer">
            isg.dev
          </a>
        </div>
      </footer>
    </main>
  );
}
