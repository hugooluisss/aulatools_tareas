import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { SchoolComponent } from './school.component';
import { SchoolService } from '../school.service';
import { ToastService } from '../../../core/services/toast.service';

describe('SchoolComponent', () => {
  let fixture: ComponentFixture<SchoolComponent>;
  const update = vi.fn(() => of({}));
  const uploadLogo = vi.fn(() => of({ logo_url: '/uploads/school/logo.png' }));
  const removeLogo = vi.fn(() => of({ logo_url: null }));

  beforeEach(() => {
    update.mockClear();
    TestBed.configureTestingModule({
      imports: [SchoolComponent],
      providers: [
        {
          provide: SchoolService,
          useValue: {
            get: () => of({ id: 1, name: 'Escuela', address: null, phone: null, email: null, logo_url: null }),
            update,
            uploadLogo,
            removeLogo,
          },
        },
        { provide: ToastService, useValue: { show: () => undefined } },
      ],
    });
    fixture = TestBed.createComponent(SchoolComponent);
    fixture.detectChanges();
  });

  it('edits and submits school contact fields', () => {
    const component = fixture.componentInstance;
    component.form.patchValue({
      name: 'Colegio',
      address: 'Centro',
      phone: '5512345678',
      email: 'info@colegio.mx',
    });
    component.save();
    expect(update).toHaveBeenCalledWith({
      name: 'Colegio',
      address: 'Centro',
      phone: '5512345678',
      email: 'info@colegio.mx',
    });
    expect(fixture.nativeElement.textContent).toContain('Dirección');
    expect(fixture.nativeElement.textContent).toContain('Teléfono');
    expect(fixture.nativeElement.textContent).toContain('Correo electrónico');
  });

  it('uploads, previews, and removes the school logo', () => {
    const input = fixture.nativeElement.querySelector('#school-logo') as HTMLInputElement;
    const file = new File(['logo'], 'logo.png', { type: 'image/png' });
    Object.defineProperty(input, 'files', { value: [file] });
    input.dispatchEvent(new Event('change'));
    expect(uploadLogo).toHaveBeenCalledWith(file);
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('img').getAttribute('src')).toContain('/uploads/school/logo.png');
    expect(fixture.nativeElement.querySelector('app-icon-button')).not.toBeNull();
    fixture.componentInstance.removeLogo();
    expect(removeLogo).toHaveBeenCalled();
    expect(fixture.componentInstance.logoUrl()).toBeNull();
  });
});
