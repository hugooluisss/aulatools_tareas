import { TestBed } from '@angular/core/testing';
import { TokenStorageService } from './token-storage.service';

describe('TokenStorageService', () => {
  beforeEach(() => {
    localStorage.clear();
    TestBed.configureTestingModule({});
  });
  it('stores, reads and removes the token', () => {
    const storage = TestBed.inject(TokenStorageService);
    storage.setToken('abc');
    expect(storage.getToken()).toBe('abc');
    storage.clear();
    expect(storage.getToken()).toBeNull();
  });
});
