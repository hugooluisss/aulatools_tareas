import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { TokenStorageService } from '../../core/auth/token-storage.service';
import { ToastService } from '../../core/services/toast.service';
import { IconButtonComponent } from '../../shared/icon-button/icon-button.component';
import { Announcement, AnnouncementsService } from './announcements.service';
import { Page } from '../../core/models/page';
import { PaginatorComponent } from '../../shared/paginator/paginator.component';

@Component({
  selector: 'app-announcements',
  standalone: true,
  imports: [DatePipe, ReactiveFormsModule, IconButtonComponent, PaginatorComponent],
  templateUrl: './announcements.component.html',
  styleUrl: './announcements.component.scss',
})
export class AnnouncementsComponent {
  private readonly fb = inject(FormBuilder);
  private readonly api = inject(AnnouncementsService);
  private readonly toast = inject(ToastService);
  readonly isAdmin = inject(TokenStorageService).getRole() === 'admin';
  readonly form = this.fb.nonNullable.group({
    title: ['', Validators.required],
    body: ['', Validators.required],
    starts_on: ['', Validators.required],
    ends_on: ['', Validators.required],
  });
  announcements = signal<Page<Announcement>>({ items: [], page: 1, per_page: 20, total: 0, total_pages: 1 });
  page = signal(1);
  formOpen = signal(false);
  editing: number | null = null;
  error = signal('');

  constructor() {
    this.load();
  }

  load(page = this.page()): void {
    this.page.set(page);
    const request = this.isAdmin ? this.api.list(page) : this.api.active(page);
    request.subscribe((result) => this.announcements.set(result));
  }

  edit(announcement: Announcement): void {
    this.editing = announcement.id;
    this.form.patchValue(announcement);
    this.formOpen.set(true);
  }

  openNew(): void {
    this.editing = null;
    this.form.reset({ title: '', body: '', starts_on: '', ends_on: '' });
    this.error.set('');
    this.formOpen.set(true);
  }

  closeForm(): void {
    this.formOpen.set(false);
    this.editing = null;
    this.form.reset({ title: '', body: '', starts_on: '', ends_on: '' });
    this.error.set('');
  }

  save(): void {
    this.error.set('');
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const announcement = this.form.getRawValue();
    if (announcement.ends_on < announcement.starts_on) {
      this.error.set('La fecha de fin debe ser igual o posterior a la fecha de inicio.');
      return;
    }
    this.api.save(this.editing, announcement).subscribe(() => this.done('Aviso guardado.'));
  }

  remove(id: number): void {
    this.api.remove(id).subscribe(() => this.done('Aviso eliminado.'));
  }

  private done(message: string): void {
    this.toast.show(message);
    this.editing = null;
    this.formOpen.set(false);
    this.form.reset({ title: '', body: '', starts_on: '', ends_on: '' });
    if (this.page() > 1 && this.announcements().items.length === 1 && message === 'Aviso eliminado.') this.load(this.page() - 1);
    else this.load();
  }
}
