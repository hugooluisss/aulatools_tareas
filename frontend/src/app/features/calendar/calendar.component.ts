import { DatePipe } from '@angular/common';
import { Component, HostListener, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ToastService } from '../../core/services/toast.service';
import { GoogleCalendarLinkService } from '../../core/services/google-calendar-link.service';
import { TokenStorageService } from '../../core/auth/token-storage.service';
import { IconButtonComponent } from '../../shared/icon-button/icon-button.component';
import { SubjectsService } from '../admin/subjects.service';
import { CalendarEvent, CalendarItem, CalendarService } from './calendar.service';
import { Page } from '../../core/models/page';
import { PaginatorComponent } from '../../shared/paginator/paginator.component';
import { forkJoin } from 'rxjs';

@Component({
  selector: 'app-calendar',
  standalone: true,
  imports: [DatePipe, ReactiveFormsModule, IconButtonComponent, PaginatorComponent],
  templateUrl: './calendar.component.html',
  styleUrl: './calendar.component.scss',
})
export class CalendarComponent {
  private readonly fb = inject(FormBuilder);
  private readonly api = inject(CalendarService);
  private readonly subjectsApi = inject(SubjectsService);
  private readonly google = inject(GoogleCalendarLinkService);
  private readonly toast = inject(ToastService);
  readonly isAdmin = inject(TokenStorageService).getRole() === 'admin';
  readonly form = this.fb.nonNullable.group({
    subject_id: [''],
    title: ['', Validators.required],
    description: [''],
    starts_at: ['', Validators.required],
    ends_at: ['', Validators.required],
  });
  items = signal<Page<CalendarItem>>({
    items: [],
    page: 1,
    per_page: 20,
    total: 0,
    total_pages: 1,
  });
  events = signal<Page<CalendarEvent>>({
    items: [],
    page: 1,
    per_page: 20,
    total: 0,
    total_pages: 1,
  });
  page = signal(1);
  eventsPage = signal(1);
  subjects = signal<{ id: string; name: string }[]>([]);
  formOpen = signal(false);
  month = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
  editing: number | null = null;
  error = signal('');
  selectedDay = signal<Date | null>(null);
  selectedItem = signal<CalendarItem | null>(null);

  constructor() {
    if (this.isAdmin) {
      this.api.events().subscribe((rows) => this.events.set(rows));
      this.subjectsApi.all().subscribe((rows) => this.subjects.set(rows));
    }
    this.load();
  }

  get monthLabel(): string {
    return this.month.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
  }

  get days(): Date[] {
    const first = new Date(this.month.getFullYear(), this.month.getMonth(), 1);
    const start = new Date(first);
    start.setDate(1 - ((first.getDay() + 6) % 7));
    return Array.from({ length: 42 }, (_, index) => {
      const day = new Date(start);
      day.setDate(start.getDate() + index);
      return day;
    });
  }

  moveMonth(amount: number): void {
    this.month = new Date(this.month.getFullYear(), this.month.getMonth() + amount, 1);
    this.selectedDay.set(null);
    this.load();
  }

  load(): void {
    const from = new Date(this.month.getFullYear(), this.month.getMonth(), 1).toISOString();
    const to = new Date(this.month.getFullYear(), this.month.getMonth() + 1, 1).toISOString();
    this.api.list(from, to, 1).subscribe((first) => {
      if (first.total_pages <= 1) {
        this.items.set(first);
        this.page.set(1);
        return;
      }
      forkJoin(
        Array.from({ length: first.total_pages - 1 }, (_, index) =>
          this.api.list(from, to, index + 2),
        ),
      ).subscribe((rest) => {
        this.items.set({
          ...first,
          items: [...first.items, ...rest.flatMap((rows) => rows.items)],
        });
        this.page.set(1);
      });
    });
  }

  selectDay(day: Date): void {
    this.selectedDay.set(day);
  }

  openItem(item: CalendarItem): void {
    this.selectedItem.set(item);
  }

  closeItem(): void {
    this.selectedItem.set(null);
  }

  @HostListener('document:keydown.escape')
  closeItemOnEscape(): void {
    this.closeItem();
  }

  dateOnly(value: string): boolean {
    return value.length === 10;
  }

  subjectName(item: CalendarItem): string {
    return (
      this.subjects().find((subject) => Number(subject.id) === item.subject_id)?.name ??
      'Toda la escuela'
    );
  }

  editItem(item: CalendarItem): void {
    const event = this.events().items.find((row) => row.id === item.id);
    if (event) this.edit(event);
  }

  loadPage(page: number): void {
    this.page.set(page);
  }

  loadEvents(page = this.eventsPage()): void {
    this.eventsPage.set(page);
    this.api.events(page).subscribe((rows) => this.events.set(rows));
  }

  itemsFor(day: Date): CalendarItem[] {
    return this.items().items.filter((item) => {
      const date = new Date(item.starts_at);
      return (
        date.getFullYear() === day.getFullYear() &&
        date.getMonth() === day.getMonth() &&
        date.getDate() === day.getDate()
      );
    });
  }

  edit(event: CalendarEvent): void {
    this.editing = event.id;
    this.form.patchValue({
      subject_id: event.subject_id?.toString() ?? '',
      title: event.title,
      description: event.description,
      starts_at: this.toLocalDateTime(event.starts_at),
      ends_at: this.toLocalDateTime(event.ends_at),
    });
    this.formOpen.set(true);
  }

  openNew(): void {
    this.editing = null;
    this.form.reset({ subject_id: '', title: '', description: '', starts_at: '', ends_at: '' });
    this.error.set('');
    this.formOpen.set(true);
  }

  closeForm(): void {
    this.formOpen.set(false);
    this.editing = null;
    this.form.reset({ subject_id: '', title: '', description: '', starts_at: '', ends_at: '' });
    this.error.set('');
  }

  save(): void {
    this.error.set('');
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    if (new Date(value.ends_at) <= new Date(value.starts_at)) {
      this.error.set('La fecha y hora de fin debe ser posterior al inicio.');
      return;
    }
    this.api
      .save(this.editing, {
        subject_id: value.subject_id ? Number(value.subject_id) : null,
        title: value.title,
        description: value.description,
        starts_at: new Date(value.starts_at).toISOString(),
        ends_at: new Date(value.ends_at).toISOString(),
      })
      .subscribe(() => this.done('Evento guardado.'));
  }

  remove(id: number): void {
    this.api.remove(id).subscribe(() => this.done('Evento eliminado.'));
  }

  addToGoogle(item: CalendarItem): void {
    const url =
      item.google_calendar_url ??
      this.google.build(
        item.title,
        item.description,
        new Date(item.starts_at),
        new Date(item.ends_at),
      );
    window.open(url, '_blank', 'noopener,noreferrer');
  }

  private done(message: string): void {
    this.toast.show(message);
    this.editing = null;
    this.formOpen.set(false);
    this.form.reset({ subject_id: '', title: '', description: '', starts_at: '', ends_at: '' });
    const page = this.eventsPage();
    this.api.events(page).subscribe((rows) => {
      if (page > 1 && !rows.items.length && rows.total_pages < page) this.loadEvents(page - 1);
      else this.events.set(rows);
    });
    this.load();
  }

  private toLocalDateTime(value: string): string {
    const date = new Date(value);
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 16);
  }
}
