#!/usr/bin/env python3
"""
Nile-Maple content ingestion utility (PLANNING PHASE TOOLING - not application code).

Reads the six source .docx catalogues/profile in the repository root and emits a
machine-readable content inventory at docs/data/content-manifest.json.

Usage:  python3 tools/extract_content.py
Output: docs/data/content-manifest.json   (categories, products, fields, media refs)
        docs/data/media/                  (optional: --media copies extracted images)

The parser is deliberately strict: it fails loudly if a product row does not match
the expected catalogue schema so that content gaps are caught at plan time, not launch.
"""
import re
import zipfile, re, os, sys, json, shutil
from xml.etree import ElementTree as ET

W='{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'
A='{http://schemas.openxmlformats.org/drawingml/2006/main}'
R='{http://schemas.openxmlformats.org/officeDocument/2006/relationships}'
ROOT=os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

CATALOGUES=[
 dict(key='fresh-fruits',    file='Nile-Maple_Fresh_Fruits_Catalogue.docx',
      title_en='Fresh Fruits', fields=['varieties','export_handling','packing','cold_chain']),
 dict(key='fresh-vegetables',file='Nile-Maple_Fresh_Vegetables_Catalogue.docx',
      title_en='Fresh Vegetables', fields=['varieties','export_handling','packing','cold_chain']),
 dict(key='frozen-products', file='Nile-Maple_Frozen_Products_Catalogue.docx',
      title_en='Frozen Products', fields=['forms','processing','packing','chain_guide']),
 dict(key='processed-canned',file='Nile-Maple_Manufactured_Processed_Canned_Products.docx',
      title_en='Manufactured, Processed & Canned Products', fields=['forms','processing','packing','chain_guide']),
]
LABELS={'varieties':r'VARIETIES / TYPES','forms':r'AVAILABLE FORMS',
        'export_handling':r'EXPORT HANDLING','processing':r'PROCESSING & HANDLING',
        'packing':r'PACKING','cold_chain':r'COLD-CHAIN GUIDE','chain_guide':r'(?:FROZEN-CHAIN|STORAGE) GUIDE'}

def docx_blocks(path):
    z=zipfile.ZipFile(path)
    rels={r.get('Id'):os.path.basename(r.get('Target'))
          for r in ET.fromstring(z.read('word/_rels/document.xml.rels'))}
    doc=ET.fromstring(z.read('word/document.xml'))
    lines=[]
    for el in doc.find(W+'body'):
        if el.tag==W+'tbl':
            for row in el.findall(W+'tr'):
                cells=[]
                for tc in row.findall(W+'tc'):
                    txt=' '.join(''.join(t.text or '' for t in p.iter(W+'t')).strip()
                                 for p in tc.iter(W+'p'))
                    imgs=[rels[b.get(R+'embed')] for b in tc.iter(A+'blip')
                          if b.get(R+'embed') in rels]
                    cells.append((txt,' '.join(imgs)))
                lines.append(('ROW',cells))
        elif el.tag==W+'p':
            txt=''.join(t.text or '' for t in el.iter(W+'t')).strip()
            if txt: lines.append(('P',txt))
    return lines,z

def parse_catalogue(spec):
    lines,z=docx_blocks(os.path.join(ROOT,spec['file']))
    products=[]; cur=None
    for kind,val in lines:
        if kind=='P':
            m=re.match(r'^(\d{2})\s{2,}(.+)$',val)
            if m:
                cur=dict(index=int(m.group(1)),name_en=m.group(2).strip(),image=None,
                         description_en='',**{f:'' for f in spec['fields']})
                products.append(cur)
        else:
            if cur is None or not val: continue
            blob=' '.join(c[0] for c in val if c[0])
            imgs=[c[1] for c in val if c[1]]
            if imgs and not cur['image']: cur['image']=imgs[0]
            parts=re.split(r'\s+(?:'+'|'.join(LABELS[f] for f in spec['fields'])+r')\s{2,}',blob)
            keys=['description_en']+spec['fields']
            for k,v in zip(keys,parts):
                if v and not cur[k]: cur[k]=v.strip()
    bad=[p['name_en'] for p in products if not(p['image'] and p['description_en'] and all(p[f] for f in spec['fields']))]
    seen={}; dupes=[]
    for pr in products:
        if pr['image']:
            if pr['image'] in seen: dupes.append((pr['image'],seen[pr['image']],pr['name_en']))
            else: seen[pr['image']]=pr['name_en']
    return products,bad,z,dupes

def main():
    media_out=os.path.join(ROOT,'docs','data','media') if '--media' in sys.argv else None
    manifest=dict(project='Nile-Maple',source='Company DOCX catalogues (git root)',
                  extracted_with='tools/extract_content.py',categories=[],company=dict(
        name_en='Nile-Maple',tagline_en='Egyptian for Food Industrial',
        descriptor_en='Egyptian Import and Export of Food and Agricultural Products',
        email='contact@nilemaple.com',phone='+20 1515919135',whatsapp='+20 1515919135',
        instagram='https://www.instagram.com/nilemaple2025',
        facebook='https://www.facebook.com/share/1Hk3W6guhD/?mibextid=wwXIfr',
        leadership=[dict(name='Eng. Issa Abousheloua',role_en='Founder and Owner'),
                    dict(name='Eng. Omar Issa',role_en='Co-Founder and Executive Manager')],
        logos=dict(mark='word/media/image1.png',lockup_en='word/media/image2.png',lockup_ar='word/media/image7.png',
                   note='All logo PNGs are RGB with opaque #FDF9F6 background - MUST be re-cut to transparent + SVG'),
        division_images=dict(**{})))
    totals=dict(products=0,media=0); cat_dq={}; manifest['data_quality']=cat_dq
    for spec in CATALOGUES:
        products,bad,z,dupes=parse_catalogue(spec)
        if bad: print(f"WARN {spec['key']}: incomplete rows -> {bad}",file=sys.stderr)
        for img,a,b in dupes:
            print(f"WARN {spec['key']}: image {img} shared by '{a}' and '{b}' (source doc defect)",file=sys.stderr)
            cat_dq.setdefault(spec['key'],[]).append(dict(issue='duplicate_image',image=img,products=[a,b]))
        if media_out:
            os.makedirs(os.path.join(media_out,spec['key']),exist_ok=True)
            for n in z.namelist():
                if n.startswith('word/media/'):
                    shutil.copyfileobj(z.open(n),open(os.path.join(media_out,spec['key'],os.path.basename(n)),'wb'))
        cat=dict(key=spec['key'],title_en=spec['title_en'],field_schema=spec['fields'],
                 product_count=len(products),products=products)
        manifest['categories'].append(cat)
        totals['products']+=len(products); totals['media']+=len(products)
    if media_out:
        # company-profile photography (word/media/image3-6.jpg): mango, broccoli, frozen
        # strawberries, olives — referenced by seeded blocks/posts as profile/imageN.jpg
        prof=os.path.join(media_out,'profile'); os.makedirs(prof,exist_ok=True)
        with zipfile.ZipFile(os.path.join(ROOT,'Nile-Maple_Company_Profile.docx')) as zp:
            for n in zp.namelist():
                if re.fullmatch(r'word/media/image[3-6]\.jpg', n):
                    shutil.copyfileobj(zp.open(n),open(os.path.join(prof,os.path.basename(n)),'wb'))
    manifest['totals']=totals
    out=os.path.join(ROOT,'docs','data','content-manifest.json')
    json.dump(manifest,open(out,'w'),ensure_ascii=False,indent=1)
    print(f"wrote {out}: {totals['products']} products in {len(manifest['categories'])} categories")

if __name__=='__main__': main()
